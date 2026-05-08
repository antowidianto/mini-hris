import api from '@/services/api'

export async function getNotifications(params = {}) {
  const response = await api.get('/notifications', { params })

  return response.data.data
}

export async function getUnreadNotificationCount() {
  const response = await api.get('/notifications/unread-count')

  return response.data.data.unread_count
}

export async function markNotificationAsRead(id) {
  const response = await api.post(`/notifications/${id}/read`)

  return response.data.data.notification
}

export async function markAllNotificationsAsRead() {
  const response = await api.post('/notifications/read-all')

  return response.data.data.updated
}
