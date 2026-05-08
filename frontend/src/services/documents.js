import api from '@/services/api'

export async function getDocuments(params = {}) {
  const response = await api.get('/documents', { params })

  return response.data.data
}

export async function getMyDocuments(params = {}) {
  const response = await api.get('/documents/my', { params })

  return response.data.data
}

export async function generateDocument(payload) {
  const response = await api.post('/documents/generate', payload)

  return response.data.data.document
}

export async function uploadDocument(payload) {
  const response = await api.post('/documents/upload', payload, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  })

  return response.data.data.document
}

export async function deleteDocument(id) {
  await api.delete(`/documents/${id}`)
}

export async function getDocumentBlob(id, action = 'preview') {
  const response = await api.get(`/documents/${id}/${action}`, {
    responseType: 'blob',
  })

  return response.data
}
