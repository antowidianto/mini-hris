import api from '@/services/api'

export async function clockIn() {
  const response = await api.post('/attendance/clock-in')

  return response.data.data.attendance
}

export async function clockOut() {
  const response = await api.post('/attendance/clock-out')

  return response.data.data.attendance
}

export async function getMyAttendance(params = {}) {
  const response = await api.get('/attendance/my', { params })

  return response.data.data
}

export async function getAttendanceReport(params = {}) {
  const response = await api.get('/attendance/report', { params })

  return response.data.data
}

export async function getMonthlyAttendanceRecap(params = {}) {
  const response = await api.get('/attendance/monthly-recap', { params })

  return response.data.data.recap
}

export async function importAttendancePlaceholder(payload) {
  const response = await api.post('/attendance/import-placeholder', payload)

  return response.data.data.import
}
