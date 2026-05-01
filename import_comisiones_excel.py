import argparse
import datetime as dt
import os
import re
import subprocess
import tempfile
import zipfile
import xml.etree.ElementTree as ET
from pathlib import Path

NS = {
    "a": "http://schemas.openxmlformats.org/spreadsheetml/2006/main",
    "r": "http://schemas.openxmlformats.org/officeDocument/2006/relationships",
}

DOC_COLS = {
    "CHK": 14,
    "SPH": 15,
    "ACO": 16,
    "LIQ": 17,
    "MES": 18,
    "REP": 19,
    "PRO": 20,
    "MER": 22,
}

INDICADOR_COLS = {
    ("VOL", "EMB"): 9,
    ("VOL", "CF"): 10,
    ("VOL", "QSO"): 11,
    ("VOL", "MTK"): 12,
    ("DF1", None): 24,
    ("DAU", None): 25,
    ("EFE", None): 26,
    ("EFI", None): 27,
    ("COB", None): 28,
    ("NSE", None): 29,
}

MONTH_NAMES = {
    1: "ENERO",
    2: "FEBRERO",
    3: "MARZO",
    4: "ABRIL",
    5: "MAYO",
    6: "JUNIO",
    7: "JULIO",
    8: "AGOSTO",
    9: "SEPTIEMBRE",
    10: "OCTUBRE",
    11: "NOVIEMBRE",
    12: "DICIEMBRE",
}


def sql(value):
    if value is None:
        return "NULL"
    if isinstance(value, bool):
        return "1" if value else "0"
    if isinstance(value, (int, float)):
        return str(round(float(value), 4))
    if isinstance(value, dt.date):
        return "'" + value.isoformat() + "'"
    return "N'" + str(value).replace("'", "''") + "'"


def col_number(ref):
    match = re.match(r"([A-Z]+)", ref)
    number = 0
    for char in match.group(1):
        number = number * 26 + ord(char) - 64
    return number


def shared_strings(zf):
    try:
        root = ET.fromstring(zf.read("xl/sharedStrings.xml"))
    except KeyError:
        return []
    return [
        "".join(t.text or "" for t in si.findall(".//a:t", NS))
        for si in root.findall("a:si", NS)
    ]


def sheet_paths(zf):
    workbook = ET.fromstring(zf.read("xl/workbook.xml"))
    rels_root = ET.fromstring(zf.read("xl/_rels/workbook.xml.rels"))
    rels = {rel.attrib["Id"]: rel.attrib["Target"] for rel in rels_root}
    paths = {}
    for sheet in workbook.findall("a:sheets/a:sheet", NS):
        rel_id = sheet.attrib["{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id"]
        paths[sheet.attrib["name"]] = "xl/" + rels[rel_id]
    return paths


def read_sheet(zf, path):
    strings = shared_strings(zf)
    root = ET.fromstring(zf.read(path))
    rows = {}
    for row in root.findall("a:sheetData/a:row", NS):
        row_num = int(row.attrib["r"])
        values = {}
        for cell in row.findall("a:c", NS):
            val_node = cell.find("a:v", NS)
            if val_node is None:
                continue
            value = val_node.text
            if cell.attrib.get("t") == "s":
                value = strings[int(value)]
            values[col_number(cell.attrib["r"])] = value
        rows[row_num] = values
    return rows


def as_int(value):
    if value in (None, ""):
        return None
    try:
        return int(float(str(value).replace(",", "")))
    except ValueError:
        return None


def as_float(value):
    if value in (None, ""):
        return 0.0
    try:
        return float(str(value).replace(",", ""))
    except ValueError:
        return 0.0


def week_from_filename(path):
    match = re.search(r"SEM[_ ]?(\d{1,2})", path.name.upper())
    if not match:
        return None
    return int(match.group(1))


def week_dates(week):
    start = dt.date(2025, 12, 29) + dt.timedelta(days=(week - 1) * 7)
    return start, start + dt.timedelta(days=5)


def month_days(start, end):
    days = {}
    current = start
    while current <= end:
        days[current.month] = days.get(current.month, 0) + 1
        current += dt.timedelta(days=1)
    return days


def workdays_in_month(year, month):
    current = dt.date(year, month, 1)
    total = 0
    while current.month == month:
        if current.weekday() < 6:
            total += 1
        current += dt.timedelta(days=1)
    return total


def parse_file(path):
    week = week_from_filename(path)
    if week is None:
        return None

    start, end = week_dates(week)
    rows = []

    with zipfile.ZipFile(path) as zf:
        base = read_sheet(zf, sheet_paths(zf)["BASE"])
        for row_num in sorted(base):
            if row_num < 8:
                continue
            row = base[row_num]
            employee_number = as_int(row.get(2))
            name = str(row.get(3) or "").strip()
            if not employee_number or not name or name.upper() == "VACANTE":
                continue
            if name.upper().startswith("TOTAL"):
                continue

            rows.append({
                "employee_number": employee_number,
                "name": name[:150],
                "sucursal": str(row.get(1) or "SIN SUCURSAL").strip()[:100],
                "ruta": str(row.get(4) or "").strip()[:30],
                "puesto": str(row.get(5) or "").strip().upper()[:30] or None,
                "canal": str(row.get(6) or "").strip().upper()[:30] or None,
                "tce": str(row.get(7) or "").strip().upper()[:5] or None,
                "comentario": str(row.get(33) or "").strip()[:300],
                "comentario_ajuste": str(row.get(40) or "").strip()[:300],
                "subtotal": as_float(row.get(31)),
                "total": as_float(row.get(32)),
                "descuento": as_float(row.get(35)),
                "sr1": as_float(row.get(36)),
                "sr2": as_float(row.get(37)),
                "agregado": as_float(row.get(38)),
                "dias_descuento": as_int(row.get(39)) or 0,
                "doc": {key: as_float(row.get(col)) for key, col in DOC_COLS.items()},
                "indicadores": {
                    key: as_float(row.get(col)) for key, col in INDICADOR_COLS.items()
                },
            })

    return {
        "path": path,
        "week": week,
        "start": start,
        "end": end,
        "rows": rows,
    }


def build_sql(imports):
    def chunks(items, size):
        for index in range(0, len(items), size):
            yield items[index:index + size]

    rows = []
    weeks = []
    months = sorted({(item["start"].year, item["start"].month) for item in imports} |
                    {(item["end"].year, item["end"].month) for item in imports})
    for item in imports:
        days = month_days(item["start"], item["end"])
        weeks.append((
            item["week"],
            item["start"],
            item["end"],
            item["start"].year,
            item["start"].month,
            item["end"].year,
            item["end"].month,
            days.get(item["start"].month, 0),
            0 if item["start"].month == item["end"].month else days.get(item["end"].month, 0),
            item["path"].name,
        ))
        for row in item["rows"]:
            rows.append((
                item["week"], item["path"].name, row["employee_number"], row["name"],
                row["sucursal"], row["ruta"], row["puesto"], row["canal"], row["tce"],
                row["comentario"] or row["comentario_ajuste"] or "Importado desde Excel",
                row["subtotal"], row["total"], row["descuento"], row["sr1"], row["sr2"],
                row["agregado"], row["dias_descuento"],
                row["indicadores"][("VOL", "EMB")], row["indicadores"][("VOL", "CF")],
                row["indicadores"][("VOL", "QSO")], row["indicadores"][("VOL", "MTK")],
                row["indicadores"][("DF1", None)], row["indicadores"][("DAU", None)],
                row["indicadores"][("EFE", None)], row["indicadores"][("EFI", None)],
                row["indicadores"][("COB", None)], row["indicadores"][("NSE", None)],
                row["doc"]["CHK"], row["doc"]["SPH"], row["doc"]["ACO"], row["doc"]["LIQ"],
                row["doc"]["MES"], row["doc"]["REP"], row["doc"]["PRO"], row["doc"]["MER"],
            ))

    def tuple_sql(values):
        return "(" + ", ".join(sql(value) for value in values) + ")"

    lines = [
        "SET QUOTED_IDENTIFIER ON;",
        "SET ANSI_NULLS ON;",
        "SET ANSI_PADDING ON;",
        "SET ANSI_WARNINGS ON;",
        "SET ARITHABORT ON;",
        "SET CONCAT_NULL_YIELDS_NULL ON;",
        "SET NUMERIC_ROUNDABORT OFF;",
        "SET XACT_ABORT ON;",
        "BEGIN TRANSACTION;",
        "DECLARE @UsuarioImporta int = 1;",
        "",
        "CREATE TABLE #Mes (Anio int, Mes int, Nombre varchar(20), DiasHabiles int);",
        "CREATE TABLE #SemanaImport (Semana int, FechaInicio date, FechaFin date, AnioInicio int, MesInicio int, AnioFin int, MesFin int, DiasMesInicio int, DiasMesFinal int, ArchivoOrigen varchar(255));",
        "CREATE TABLE #ImportBase (Semana int, ArchivoOrigen varchar(255), Numero_Empleado int, NombreEmpleado varchar(150), Sucursal varchar(100), Ruta varchar(30), Puesto varchar(30), Canal varchar(30), TCE varchar(5), Comentario varchar(300), SubTotal decimal(18,4), Total decimal(18,4), Descuento decimal(18,4), SR1 decimal(18,4), SR2 decimal(18,4), Agregado decimal(18,4), DiasDescuento int, VOL_EMB decimal(18,4), VOL_CF decimal(18,4), VOL_QSO decimal(18,4), VOL_MTK decimal(18,4), DF1 decimal(18,4), DAU decimal(18,4), EFE decimal(18,4), EFI decimal(18,4), COB decimal(18,4), NSE decimal(18,4), DOC_CHK decimal(18,4), DOC_SPH decimal(18,4), DOC_ACO decimal(18,4), DOC_LIQ decimal(18,4), DOC_MES decimal(18,4), DOC_REP decimal(18,4), DOC_PRO decimal(18,4), DOC_MER decimal(18,4));",
        "",
    ]

    if months:
        lines.append("INSERT INTO #Mes (Anio, Mes, Nombre, DiasHabiles) VALUES")
        lines.append(",\n".join(tuple_sql((year, month, MONTH_NAMES[month], workdays_in_month(year, month))) for year, month in months) + ";")
    if weeks:
        lines.append("INSERT INTO #SemanaImport (Semana, FechaInicio, FechaFin, AnioInicio, MesInicio, AnioFin, MesFin, DiasMesInicio, DiasMesFinal, ArchivoOrigen) VALUES")
        lines.append(",\n".join(tuple_sql(row) for row in weeks) + ";")
    for batch in chunks(rows, 200):
        lines.append("INSERT INTO #ImportBase (Semana, ArchivoOrigen, Numero_Empleado, NombreEmpleado, Sucursal, Ruta, Puesto, Canal, TCE, Comentario, SubTotal, Total, Descuento, SR1, SR2, Agregado, DiasDescuento, VOL_EMB, VOL_CF, VOL_QSO, VOL_MTK, DF1, DAU, EFE, EFI, COB, NSE, DOC_CHK, DOC_SPH, DOC_ACO, DOC_LIQ, DOC_MES, DOC_REP, DOC_PRO, DOC_MER) VALUES")
        lines.append(",\n".join(tuple_sql(row) for row in batch) + ";")

    lines.extend([
        "",
        "INSERT INTO com.meta_mes_portada (Anio, Mes, Nombre, DiasHabiles, ID_UsuarioCreo)",
        "SELECT m.Anio, m.Mes, m.Nombre, m.DiasHabiles, @UsuarioImporta",
        "FROM #Mes m",
        "WHERE NOT EXISTS (SELECT 1 FROM com.meta_mes_portada p WHERE p.Anio = m.Anio AND p.Mes = m.Mes);",
        "",
        "INSERT INTO com.semana (Anio, Semana, FechaInicio, FechaFin, ID_MetaMesInicio, ID_MetaMesFinal, DiasMesInicio, DiasMesFinal, ID_UsuarioCreo, Activo)",
        "SELECT 2026, s.Semana, s.FechaInicio, s.FechaFin, mi.ID_MetaMes, mf.ID_MetaMes, s.DiasMesInicio, s.DiasMesFinal, @UsuarioImporta, 1",
        "FROM #SemanaImport s",
        "JOIN com.meta_mes_portada mi ON mi.Anio = s.AnioInicio AND mi.Mes = s.MesInicio",
        "JOIN com.meta_mes_portada mf ON mf.Anio = s.AnioFin AND mf.Mes = s.MesFin",
        "WHERE NOT EXISTS (SELECT 1 FROM com.semana sem WHERE sem.Anio = 2026 AND sem.Semana = s.Semana);",
        "",
        ";WITH Empleados AS (",
        "    SELECT *, ROW_NUMBER() OVER (PARTITION BY Numero_Empleado ORDER BY Semana DESC) AS rn",
        "    FROM #ImportBase",
        ")",
        "INSERT INTO core.empleado (Numero_Empleado, Nombre, ID_Sucursal, ID_Area, Activo)",
        "SELECT e.Numero_Empleado, e.NombreEmpleado, suc.ID_Sucursal, 18, 1",
        "FROM Empleados e",
        "LEFT JOIN core.sucursal suc ON UPPER(suc.Nombre) = UPPER(e.Sucursal)",
        "WHERE e.rn = 1 AND NOT EXISTS (SELECT 1 FROM core.empleado ce WHERE ce.Numero_Empleado = e.Numero_Empleado);",
        "",
        ";WITH BasesImportadas AS (",
        "    SELECT b.ID_Base",
        "    FROM com.base_comision_semanal b",
        "    JOIN com.corrida_comision c ON c.ID_Corrida = b.ID_Corrida",
        "    JOIN #SemanaImport s ON s.ArchivoOrigen = c.ArchivoOrigen",
        ")",
        "DELETE FROM com.calculo_comision WHERE ID_Base IN (SELECT ID_Base FROM BasesImportadas);",
        ";WITH BasesImportadas AS (",
        "    SELECT b.ID_Base FROM com.base_comision_semanal b JOIN com.corrida_comision c ON c.ID_Corrida = b.ID_Corrida JOIN #SemanaImport s ON s.ArchivoOrigen = c.ArchivoOrigen",
        ")",
        "DELETE FROM com.ajuste_comision WHERE ID_Base IN (SELECT ID_Base FROM BasesImportadas);",
        ";WITH BasesImportadas AS (",
        "    SELECT b.ID_Base FROM com.base_comision_semanal b JOIN com.corrida_comision c ON c.ID_Corrida = b.ID_Corrida JOIN #SemanaImport s ON s.ArchivoOrigen = c.ArchivoOrigen",
        ")",
        "DELETE FROM com.resultado_doc WHERE ID_Base IN (SELECT ID_Base FROM BasesImportadas);",
        ";WITH BasesImportadas AS (",
        "    SELECT b.ID_Base FROM com.base_comision_semanal b JOIN com.corrida_comision c ON c.ID_Corrida = b.ID_Corrida JOIN #SemanaImport s ON s.ArchivoOrigen = c.ArchivoOrigen",
        ")",
        "DELETE FROM com.resultado_indicador WHERE ID_Base IN (SELECT ID_Base FROM BasesImportadas);",
        "DELETE b FROM com.base_comision_semanal b JOIN com.corrida_comision c ON c.ID_Corrida = b.ID_Corrida JOIN #SemanaImport s ON s.ArchivoOrigen = c.ArchivoOrigen;",
        "DELETE c FROM com.corrida_comision c JOIN #SemanaImport s ON s.ArchivoOrigen = c.ArchivoOrigen;",
        "",
        "INSERT INTO com.corrida_comision (ID_Semana, FechaInicio, FechaFin, Estatus, ArchivoOrigen, Observaciones, ID_UsuarioCreo)",
        "SELECT sem.ID_Semana, s.FechaInicio, s.FechaFin, 'CALCULADO', s.ArchivoOrigen, 'Importado desde Excel historico', @UsuarioImporta",
        "FROM #SemanaImport s",
        "JOIN com.semana sem ON sem.Anio = 2026 AND sem.Semana = s.Semana;",
        "",
        "INSERT INTO com.base_comision_semanal (ID_Corrida, Numero_Empleado, Sucursal, NombreEmpleado, Ruta, Puesto, Canal, TCE, Activo, ID_UsuarioCreo)",
        "SELECT c.ID_Corrida, b.Numero_Empleado, b.Sucursal, b.NombreEmpleado, b.Ruta, b.Puesto, b.Canal, b.TCE, 1, @UsuarioImporta",
        "FROM #ImportBase b",
        "JOIN com.corrida_comision c ON c.ArchivoOrigen = b.ArchivoOrigen;",
        "",
        "INSERT INTO com.resultado_indicador (ID_Base, ID_Indicador, ID_SubIndicador, ValorReal, Meta, PorcentajeCumplimiento, MontoCalculado, Observaciones, ID_UsuarioCreo)",
        "SELECT bs.ID_Base, ind.ID_Indicador, sub.ID_SubIndicador, v.Monto, 0, NULL, v.Monto, b.Comentario, @UsuarioImporta",
        "FROM #ImportBase b",
        "JOIN com.corrida_comision c ON c.ArchivoOrigen = b.ArchivoOrigen",
        "JOIN com.base_comision_semanal bs ON bs.ID_Corrida = c.ID_Corrida AND bs.Numero_Empleado = b.Numero_Empleado",
        "CROSS APPLY (VALUES",
        "    ('VOL','EMB',b.VOL_EMB), ('VOL','CF',b.VOL_CF), ('VOL','QSO',b.VOL_QSO), ('VOL','MTK',b.VOL_MTK),",
        "    ('DF1',NULL,b.DF1), ('DAU',NULL,b.DAU), ('EFE',NULL,b.EFE), ('EFI',NULL,b.EFI), ('COB',NULL,b.COB), ('NSE',NULL,b.NSE)",
        ") v(ClaveIndicador, ClaveSub, Monto)",
        "JOIN com.indicador ind ON ind.Clave = v.ClaveIndicador",
        "LEFT JOIN com.sub_indicador sub ON sub.ID_Indicador = ind.ID_Indicador AND sub.Clave = v.ClaveSub;",
        "",
        "INSERT INTO com.resultado_doc (ID_Base, ID_SubIndicador, Cumplido, MontoConcepto, AlcancePesos, Observaciones, ID_UsuarioCreo)",
        "SELECT bs.ID_Base, sub.ID_SubIndicador, CASE WHEN v.Monto > 0 THEN 1 ELSE 0 END, v.Monto, v.Monto, b.Comentario, @UsuarioImporta",
        "FROM #ImportBase b",
        "JOIN com.corrida_comision c ON c.ArchivoOrigen = b.ArchivoOrigen",
        "JOIN com.base_comision_semanal bs ON bs.ID_Corrida = c.ID_Corrida AND bs.Numero_Empleado = b.Numero_Empleado",
        "CROSS APPLY (VALUES",
        "    ('CHK',b.DOC_CHK), ('SPH',b.DOC_SPH), ('ACO',b.DOC_ACO), ('LIQ',b.DOC_LIQ),",
        "    ('MES',b.DOC_MES), ('REP',b.DOC_REP), ('PRO',b.DOC_PRO), ('MER',b.DOC_MER)",
        ") v(ClaveSub, Monto)",
        "JOIN com.indicador ind ON ind.Clave = 'DOC'",
        "JOIN com.sub_indicador sub ON sub.ID_Indicador = ind.ID_Indicador AND sub.Clave = v.ClaveSub;",
        "",
        "INSERT INTO com.ajuste_comision (ID_Base, TipoAjuste, DiasDescuento, Monto, Motivo, ID_UsuarioCreo)",
        "SELECT bs.ID_Base, v.TipoAjuste, b.DiasDescuento, v.Monto, b.Comentario, @UsuarioImporta",
        "FROM #ImportBase b",
        "JOIN com.corrida_comision c ON c.ArchivoOrigen = b.ArchivoOrigen",
        "JOIN com.base_comision_semanal bs ON bs.ID_Corrida = c.ID_Corrida AND bs.Numero_Empleado = b.Numero_Empleado",
        "CROSS APPLY (VALUES ('SR1', b.SR1), ('SR2', b.SR2), ('AGREGADO', b.Agregado)) v(TipoAjuste, Monto)",
        "WHERE v.Monto > 0;",
        "",
        "INSERT INTO com.calculo_comision (ID_Base, MontoBruto, TotalDescuentos, TotalAgregados, MontoFinal, Estatus, CalculadoPor, Aprobado, Observaciones)",
        "SELECT bs.ID_Base, b.SubTotal, b.Descuento, b.Agregado, b.Total, 'CALCULADO', @UsuarioImporta, 0, b.Comentario",
        "FROM #ImportBase b",
        "JOIN com.corrida_comision c ON c.ArchivoOrigen = b.ArchivoOrigen",
        "JOIN com.base_comision_semanal bs ON bs.ID_Corrida = c.ID_Corrida AND bs.Numero_Empleado = b.Numero_Empleado;",
        "",
    ])

    lines.extend(["", "COMMIT TRANSACTION;"])
    return "\n".join(lines)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--downloads", default=r"C:\Users\elfeo\Downloads")
    parser.add_argument("--server", default="localhost")
    parser.add_argument("--database", default="portalV2")
    parser.add_argument("--user", default=os.getenv("PORTALV2_DB_USER"))
    parser.add_argument("--password", default=os.getenv("PORTALV2_DB_PASSWORD"))
    parser.add_argument("--execute", action="store_true")
    args = parser.parse_args()
    if args.execute and (not args.user or not args.password):
        parser.error("Use --user/--password or set PORTALV2_DB_USER and PORTALV2_DB_PASSWORD.")

    downloads = Path(args.downloads)
    files = sorted(downloads.glob("CI-FIN-TIC-FT-008 - COM_VEN*.xlsx"))
    imports = []
    for path in files:
        week = week_from_filename(path)
        if week is None or week < 1 or week > 14:
            continue
        parsed = parse_file(path)
        if parsed:
            imports.append(parsed)

    print(f"Archivos detectados para importar: {len(imports)}")
    for item in imports:
        print(f"  Semana {item['week']:02d}: {item['path'].name} ({len(item['rows'])} registros)")

    total_rows = sum(len(item["rows"]) for item in imports)
    print(f"Total registros base: {total_rows}")

    script = build_sql(imports)
    sql_path = Path(tempfile.gettempdir()) / "portalv2_import_comisiones.sql"
    sql_path.write_text(script, encoding="utf-8")
    print(f"SQL generado: {sql_path}")

    if not args.execute:
        print("Modo revision. Agrega --execute para cargar en SQL Server.")
        return

    command = [
        "sqlcmd",
        "-S", args.server,
        "-d", args.database,
        "-U", args.user,
        "-P", args.password,
        "-b",
        "-i", str(sql_path),
    ]
    subprocess.run(command, check=True)
    print("Importacion completada.")


if __name__ == "__main__":
    main()
