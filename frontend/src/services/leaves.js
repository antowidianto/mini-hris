import api from '@/services/api'

export async function getLeaveTypes() {
  const response = await api.get('/leaves/types')

  return response.data.data.leave_types
}

export async function getLeaveBalances() {
  const response = await api.get('/leaves/balances')

  return response.data.data.leave_balances
}

export async function getLeaveRequests(params = {}) {
  const response = await api.get('/leaves', { params })

  return response.data.data
}

export async function submitLeaveRequest(payload) {
  const response = await api.post('/leaves', payload)

  return response.data.data.leave_request
}

export async function getLeaveApprovals(params = {}) {
  const response = await api.get('/leaves/approvals', { params })

  return response.data.data
}

export async function getSupervisorLeaveApprovals(params = {}) {
  const response = await api.get('/leaves/supervisor-approvals', { params })

  return response.data.data
}

export async function approveLeaveRequest(id, payload = {}) {
  const response = await api.post(`/leaves/${id}/approve`, payload)

  return response.data.data.leave_request
}

export async function rejectLeaveRequest(id, payload = {}) {
  const response = await api.post(`/leaves/${id}/reject`, payload)

  return response.data.data.leave_request
}

export async function supervisorApproveLeaveRequest(id, payload = {}) {
  const response = await api.post(`/leaves/${id}/supervisor-approve`, payload)

  return response.data.data.leave_request
}

export async function supervisorRejectLeaveRequest(id, payload = {}) {
  const response = await api.post(`/leaves/${id}/supervisor-reject`, payload)

  return response.data.data.leave_request
}
