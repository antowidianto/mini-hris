import api from '@/services/api'

export async function getOperationalReport(params = {}) {
  const response = await api.get('/reports/operational', { params })

  return response.data.data.report
}
