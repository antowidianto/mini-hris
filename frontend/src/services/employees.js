import api from '@/services/api'

export async function getEmployees(params = {}) {
  const response = await api.get('/employees', { params })

  return response.data.data
}

export async function getEmployee(id) {
  const response = await api.get(`/employees/${id}`)

  return response.data.data.employee
}

export async function createEmployee(payload) {
  const response = await api.post('/employees', payload)

  return response.data.data.employee
}

export async function updateEmployee(id, payload) {
  const response = await api.put(`/employees/${id}`, payload)

  return response.data.data.employee
}

export async function deactivateEmployee(id) {
  const response = await api.delete(`/employees/${id}`)

  return response.data.data.employee
}

export async function getDepartments() {
  const response = await api.get('/departments')

  return response.data.data.departments
}

export async function getBranches() {
  const response = await api.get('/branches')

  return response.data.data.branches
}

export async function getPositions(params = {}) {
  const response = await api.get('/positions', { params })

  return response.data.data.positions
}

export async function getSupervisors(params = {}) {
  const response = await api.get('/employees/supervisors', { params })

  return response.data.data.supervisors
}
