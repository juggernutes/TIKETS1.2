/**
 * Formatea una fecha ISO a dd/mm/yyyy hh:mm
 */
export function formatFecha(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  return d.toLocaleDateString('es-MX', { day:'2-digit', month:'2-digit', year:'numeric' })
    + ' ' + d.toLocaleTimeString('es-MX', { hour:'2-digit', minute:'2-digit' })
}

/**
 * Formatea moneda MXN
 */
export function formatMoneda(n) {
  return Number(n ?? 0).toLocaleString('es-MX', { style:'currency', currency:'MXN' })
}
