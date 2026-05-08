import api from '@/services/api'

export async function getExpiringContracts(params = {}) {
  const response = await api.get('/contracts/expiring', { params })

  return response.data.data
}

export async function getEmployeeContracts(employeeId) {
  const response = await api.get(`/employees/${employeeId}/contracts`)

  return response.data.data.contracts
}

export async function renewEmployeeContract(employeeId, payload) {
  const response = await api.post(`/employees/${employeeId}/contracts`, payload)

  return response.data.data.contract
}
