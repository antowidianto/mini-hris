import api from '@/services/api'

export async function getPayrolls(params = {}) {
  const response = await api.get('/payroll', { params })

  return response.data.data
}

export async function generatePayroll(payload) {
  const response = await api.post('/payroll/generate', payload)

  return response.data.data.payrolls
}

export async function getPayroll(id) {
  const response = await api.get(`/payroll/${id}`)

  return response.data.data.payroll
}

export async function approvePayroll(id, payload = {}) {
  const response = await api.post(`/payroll/${id}/approve`, payload)

  return response.data.data.payroll
}

export async function rejectPayroll(id, payload = {}) {
  const response = await api.post(`/payroll/${id}/reject`, payload)

  return response.data.data.payroll
}

export async function getPayslips(params = {}) {
  const response = await api.get('/payslips', { params })

  return response.data.data
}

export async function getPayslip(id) {
  const response = await api.get(`/payslips/${id}`)

  return response.data.data.payroll
}
