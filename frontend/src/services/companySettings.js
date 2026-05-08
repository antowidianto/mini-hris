import api from '@/services/api'

export async function getCompanySettingsBundle() {
  const response = await api.get('/company-settings')

  return response.data.data
}

export async function getCompanySettings() {
  const data = await getCompanySettingsBundle()

  return data.company_settings
}

export async function updateCompanySettings(payload) {
  const response = await api.put('/company-settings', payload)

  return response.data.data.company_settings
}

export async function updateConfigurationSettings(settings) {
  const response = await api.put('/settings', { settings })

  return response.data.data.settings
}

export async function updatePayrollComponent(id, payload) {
  const response = await api.put(`/payroll-components/${id}`, payload)

  return response.data.data.payroll_component
}

export async function replaceApprovalFlows(flows) {
  const response = await api.put('/approval-flows', { flows })

  return response.data.data.approval_flows
}
