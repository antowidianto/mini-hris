import api from '@/services/api'

export async function getImportJobs(params = {}) {
  const response = await api.get('/import-jobs', { params })

  return response.data.data
}

export async function importEmployeesCsv(file) {
  const payload = new FormData()
  payload.append('file', file)

  const response = await api.post('/imports/employees', payload, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  })

  return response.data.data.import_job
}

export async function importAttendanceCsv(file, source = 'fingerprint') {
  const payload = new FormData()
  payload.append('file', file)
  payload.append('source', source)

  const response = await api.post('/imports/attendance', payload, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  })

  return response.data.data.import_job
}

export async function downloadExport(path, params = {}) {
  const response = await api.get(path, {
    params,
    responseType: 'blob',
  })
  const disposition = response.headers['content-disposition'] ?? ''
  const fileName = disposition.match(/filename="?([^"]+)"?/)?.[1] ?? 'export.csv'
  const url = URL.createObjectURL(response.data)
  const link = window.document.createElement('a')
  link.href = url
  link.download = fileName
  link.click()
  URL.revokeObjectURL(url)
}
