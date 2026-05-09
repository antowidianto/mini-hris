<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'

import { getEmployees } from '@/services/employees'
import { deleteDocument, generateDocument, getDocumentBlob, getDocuments, uploadDocument } from '@/services/documents'
import { getPayrolls } from '@/services/payroll'

const documentTypes = [
  { value: 'employee_file', label: 'Employee File' },
  { value: 'payslip', label: 'Payslip' },
  { value: 'employment_certificate', label: 'Employment Certificate' },
  { value: 'contract_letter', label: 'Contract Letter' },
  { value: 'warning_letter', label: 'Warning Letter' },
]

const generatedTypes = documentTypes.filter((type) => type.value !== 'employee_file')

const filters = reactive({
  search: '',
  type: '',
  source: '',
  employee_id: '',
  per_page: 10,
  page: 1,
})

const generateForm = reactive({
  employee_id: '',
  type: 'employment_certificate',
  payroll_id: '',
  title: '',
  document_number: '',
  issue_date: new Date().toISOString().slice(0, 10),
  effective_date: '',
  warning_level: 'SP1',
  notes: '',
  signer_name: '',
  signer_title: 'HR Manager',
})

const uploadForm = reactive({
  employee_id: '',
  type: 'employee_file',
  title: '',
  document_number: '',
  issue_date: new Date().toISOString().slice(0, 10),
  notes: '',
  file: null,
})

const documents = ref([])
const employees = ref([])
const payrolls = ref([])
const meta = ref(null)
const loading = ref(false)
const employeesLoading = ref(false)
const payrollsLoading = ref(false)
const generating = ref(false)
const uploading = ref(false)
const fileWorkingId = ref(null)
const deletingId = ref(null)
const error = ref(null)
const success = ref(null)
const validationErrors = ref({})

const requiresPayroll = computed(() => generateForm.type === 'payslip')
const requiresWarningDetails = computed(() => generateForm.type === 'warning_letter')

function documentTypeLabel(type) {
  return documentTypes.find((item) => item.value === type)?.label ?? type
}

function employeeLabel(employee) {
  return `${employee.employee_id} - ${employee.full_name}`
}

function fileSize(bytes) {
  if (!bytes) {
    return '-'
  }

  if (bytes < 1024 * 1024) {
    return `${Math.round(bytes / 1024)} KB`
  }

  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

function resetFeedback() {
  error.value = null
  success.value = null
  validationErrors.value = {}
}

function fieldError(field) {
  return validationErrors.value[field]?.[0]
}

async function loadDocuments(page = 1) {
  loading.value = true
  error.value = null
  filters.page = page

  try {
    const data = await getDocuments(filters)
    documents.value = data.documents
    meta.value = data.meta
  } catch {
    error.value = 'Unable to load documents'
  } finally {
    loading.value = false
  }
}

async function loadEmployees() {
  employeesLoading.value = true

  try {
    const data = await getEmployees({ per_page: 50, employment_status: 'active' })
    employees.value = data.employees
  } finally {
    employeesLoading.value = false
  }
}

async function loadPayrollOptions() {
  payrolls.value = []

  if (!requiresPayroll.value || !generateForm.employee_id) {
    return
  }

  payrollsLoading.value = true

  try {
    const data = await getPayrolls({ employee_id: generateForm.employee_id, per_page: 50 })
    payrolls.value = data.payrolls
    generateForm.payroll_id = payrolls.value[0]?.id ?? ''
  } finally {
    payrollsLoading.value = false
  }
}

async function submitGenerate() {
  generating.value = true
  resetFeedback()

  try {
    await generateDocument({
      employee_id: generateForm.employee_id,
      type: generateForm.type,
      payroll_id: generateForm.payroll_id || null,
      title: generateForm.title || null,
      document_number: generateForm.document_number || null,
      issue_date: generateForm.issue_date || null,
      effective_date: generateForm.effective_date || null,
      warning_level: generateForm.warning_level || null,
      notes: generateForm.notes || null,
      signer_name: generateForm.signer_name || null,
      signer_title: generateForm.signer_title || null,
    })
    success.value = 'Document generated.'
    await loadDocuments(filters.page)
  } catch (requestError) {
    validationErrors.value = requestError.response?.data?.errors ?? {}
    error.value = requestError.response?.data?.message ?? 'Unable to generate document'
  } finally {
    generating.value = false
  }
}

async function submitUpload() {
  if (!uploadForm.file) {
    validationErrors.value = { file: ['Choose a file to upload.'] }
    return
  }

  uploading.value = true
  resetFeedback()

  const payload = new FormData()
  Object.entries(uploadForm).forEach(([key, value]) => {
    if (value !== '' && value !== null) {
      payload.append(key, value)
    }
  })

  try {
    await uploadDocument(payload)
    success.value = 'Document uploaded.'
    uploadForm.title = ''
    uploadForm.document_number = ''
    uploadForm.notes = ''
    uploadForm.file = null
    await loadDocuments(filters.page)
  } catch (requestError) {
    validationErrors.value = requestError.response?.data?.errors ?? {}
    error.value = requestError.response?.data?.message ?? 'Unable to upload document'
  } finally {
    uploading.value = false
  }
}

async function openDocument(document, action = 'preview') {
  fileWorkingId.value = document.id
  error.value = null

  try {
    const blob = await getDocumentBlob(document.id, action)
    const url = URL.createObjectURL(blob)

    if (action === 'download') {
      const link = window.document.createElement('a')
      link.href = url
      link.download = document.original_file_name ?? `${document.title}.pdf`
      link.click()
      URL.revokeObjectURL(url)
    } else {
      window.open(url, '_blank', 'noopener')
    }
  } catch {
    error.value = 'Unable to open document'
  } finally {
    fileWorkingId.value = null
  }
}

async function removeDocument(document) {
  if (!window.confirm(`Delete ${document.title}?`)) {
    return
  }

  deletingId.value = document.id
  resetFeedback()

  try {
    await deleteDocument(document.id)
    success.value = 'Document deleted.'
    await loadDocuments(filters.page)
  } catch {
    error.value = 'Unable to delete document'
  } finally {
    deletingId.value = null
  }
}

function onFileChange(event) {
  uploadForm.file = event.target.files?.[0] ?? null
}

watch(() => [generateForm.type, generateForm.employee_id], loadPayrollOptions)

onMounted(async () => {
  await Promise.all([loadEmployees(), loadDocuments()])
})
</script>

<template>
  <section class="mx-auto max-w-screen-2xl">
    <div class="flex flex-col justify-between gap-4 border-b border-hris-border pb-5 sm:flex-row">
      <div>
        <p class="text-xs font-semibold uppercase text-hris-accent">People</p>
        <h2 class="mt-1 text-2xl font-semibold">Documents</h2>
        <p class="mt-1 text-sm text-hris-muted">Generate and store employee HR documents.</p>
      </div>
    </div>

    <form class="mt-5 flex flex-wrap gap-3" @submit.prevent="loadDocuments(1)">
      <input
        v-model="filters.search"
        class="min-w-60 rounded-md border border-hris-border px-3 py-2 text-sm"
        placeholder="Search title, number, employee"
      />
      <select v-model="filters.employee_id" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="">All employees</option>
        <option v-for="employee in employees" :key="employee.id" :value="employee.id">
          {{ employeeLabel(employee) }}
        </option>
      </select>
      <select v-model="filters.type" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="">All types</option>
        <option v-for="type in documentTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
      </select>
      <select v-model="filters.source" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="">All sources</option>
        <option value="generated">Generated</option>
        <option value="uploaded">Uploaded</option>
      </select>
      <button
        type="submit"
        class="rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark"
      >
        Apply
      </button>
    </form>

    <div v-if="error" class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>
    <div v-if="success" class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ success }}
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-[1.35fr_0.95fr]">
      <div class="overflow-hidden rounded-md border border-hris-border bg-hris-panel">
        <div v-if="loading" class="p-6 text-sm text-hris-muted">Loading documents...</div>
        <div v-else-if="documents.length === 0" class="p-6 text-sm text-hris-muted">No documents found.</div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-hris-border text-sm">
            <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
              <tr>
                <th class="px-4 py-3 font-semibold">Document</th>
                <th class="px-4 py-3 font-semibold">Employee</th>
                <th class="px-4 py-3 font-semibold">Issue Date</th>
                <th class="px-4 py-3 font-semibold">Source</th>
                <th class="px-4 py-3 font-semibold">File</th>
                <th class="px-4 py-3 font-semibold">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-hris-border">
              <tr v-for="document in documents" :key="document.id">
                <td class="px-4 py-3">
                  <p class="font-medium">{{ document.title }}</p>
                  <p class="text-xs text-hris-muted">{{ documentTypeLabel(document.type) }} - {{ document.document_number ?? '-' }}</p>
                </td>
                <td class="px-4 py-3">
                  <p>{{ document.employee?.full_name }}</p>
                  <p class="text-xs text-hris-muted">{{ document.employee?.employee_id }}</p>
                </td>
                <td class="px-4 py-3">{{ document.issue_date ?? '-' }}</td>
                <td class="px-4 py-3">
                  <span class="rounded-md bg-hris-surface px-2 py-1 text-xs font-semibold capitalize">
                    {{ document.source }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <p>{{ document.original_file_name ?? 'Generated PDF' }}</p>
                  <p class="text-xs text-hris-muted">{{ fileSize(document.file_size) }}</p>
                </td>
                <td class="px-4 py-3">
                  <div class="flex flex-wrap gap-2">
                    <button
                      type="button"
                      class="text-hris-primary hover:underline disabled:opacity-50"
                      :disabled="fileWorkingId === document.id"
                      @click="openDocument(document, 'preview')"
                    >
                      Preview
                    </button>
                    <button
                      type="button"
                      class="text-hris-primary hover:underline disabled:opacity-50"
                      :disabled="fileWorkingId === document.id"
                      @click="openDocument(document, 'download')"
                    >
                      Download
                    </button>
                    <button
                      type="button"
                      class="text-red-600 hover:underline disabled:opacity-50"
                      :disabled="deletingId === document.id"
                      @click="removeDocument(document)"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="meta" class="flex items-center justify-between gap-3 border-t border-hris-border px-4 py-3 text-sm">
          <p class="text-hris-muted">Page {{ meta.current_page }} of {{ meta.last_page }}</p>
          <div class="flex gap-2">
            <button
              type="button"
              class="rounded-md border border-hris-border px-3 py-2 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="meta.current_page <= 1"
              @click="loadDocuments(meta.current_page - 1)"
            >
              Previous
            </button>
            <button
              type="button"
              class="rounded-md border border-hris-border px-3 py-2 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="meta.current_page >= meta.last_page"
              @click="loadDocuments(meta.current_page + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      <aside class="space-y-5">
        <form class="rounded-md border border-hris-border bg-hris-panel p-5" @submit.prevent="submitGenerate">
          <h3 class="font-semibold">Generate Document</h3>
          <div class="mt-4 grid gap-3">
            <label class="block">
              <span class="text-xs font-medium text-hris-muted">Employee</span>
              <select v-model="generateForm.employee_id" required class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm">
                <option value="" disabled>{{ employeesLoading ? 'Loading employees...' : 'Select employee' }}</option>
                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                  {{ employeeLabel(employee) }}
                </option>
              </select>
              <span v-if="fieldError('employee_id')" class="mt-1 block text-xs text-red-600">{{ fieldError('employee_id') }}</span>
            </label>
            <label class="block">
              <span class="text-xs font-medium text-hris-muted">Type</span>
              <select v-model="generateForm.type" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm">
                <option v-for="type in generatedTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
              </select>
              <span v-if="fieldError('type')" class="mt-1 block text-xs text-red-600">{{ fieldError('type') }}</span>
            </label>
            <label v-if="requiresPayroll" class="block">
              <span class="text-xs font-medium text-hris-muted">Payroll</span>
              <select v-model="generateForm.payroll_id" required class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm">
                <option value="" disabled>{{ payrollsLoading ? 'Loading payroll...' : 'Select payroll' }}</option>
                <option v-for="payroll in payrolls" :key="payroll.id" :value="payroll.id">
                  {{ payroll.period_label }} - {{ payroll.employee?.full_name }}
                </option>
              </select>
              <span v-if="fieldError('payroll_id')" class="mt-1 block text-xs text-red-600">{{ fieldError('payroll_id') }}</span>
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
              <label class="block">
                <span class="text-xs font-medium text-hris-muted">Document number</span>
                <input v-model="generateForm.document_number" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
                <span v-if="fieldError('document_number')" class="mt-1 block text-xs text-red-600">{{ fieldError('document_number') }}</span>
              </label>
              <label class="block">
                <span class="text-xs font-medium text-hris-muted">Issue date</span>
                <input v-model="generateForm.issue_date" type="date" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
                <span v-if="fieldError('issue_date')" class="mt-1 block text-xs text-red-600">{{ fieldError('issue_date') }}</span>
              </label>
            </div>
            <label class="block">
              <span class="text-xs font-medium text-hris-muted">Title</span>
              <input v-model="generateForm.title" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
              <span v-if="fieldError('title')" class="mt-1 block text-xs text-red-600">{{ fieldError('title') }}</span>
            </label>
            <div v-if="requiresWarningDetails" class="grid gap-3 sm:grid-cols-2">
              <label class="block">
                <span class="text-xs font-medium text-hris-muted">Warning level</span>
                <select v-model="generateForm.warning_level" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm">
                  <option value="SP1">SP1</option>
                  <option value="SP2">SP2</option>
                  <option value="SP3">SP3</option>
                </select>
                <span v-if="fieldError('warning_level')" class="mt-1 block text-xs text-red-600">{{ fieldError('warning_level') }}</span>
              </label>
              <label class="block">
                <span class="text-xs font-medium text-hris-muted">Effective date</span>
                <input v-model="generateForm.effective_date" type="date" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
                <span v-if="fieldError('effective_date')" class="mt-1 block text-xs text-red-600">{{ fieldError('effective_date') }}</span>
              </label>
            </div>
            <label class="block">
              <span class="text-xs font-medium text-hris-muted">Notes</span>
              <textarea v-model="generateForm.notes" rows="3" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"></textarea>
              <span v-if="fieldError('notes')" class="mt-1 block text-xs text-red-600">{{ fieldError('notes') }}</span>
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
              <label class="block">
                <span class="text-xs font-medium text-hris-muted">Signer name</span>
                <input v-model="generateForm.signer_name" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
              </label>
              <label class="block">
                <span class="text-xs font-medium text-hris-muted">Signer title</span>
                <input v-model="generateForm.signer_title" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
              </label>
            </div>
            <button
              type="submit"
              class="rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="generating"
            >
              {{ generating ? 'Generating...' : 'Generate' }}
            </button>
          </div>
        </form>

        <form class="rounded-md border border-hris-border bg-hris-panel p-5" @submit.prevent="submitUpload">
          <h3 class="font-semibold">Upload Document</h3>
          <div class="mt-4 grid gap-3">
            <label class="block">
              <span class="text-xs font-medium text-hris-muted">Employee</span>
              <select v-model="uploadForm.employee_id" required class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm">
                <option value="" disabled>Select employee</option>
                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                  {{ employeeLabel(employee) }}
                </option>
              </select>
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
              <label class="block">
                <span class="text-xs font-medium text-hris-muted">Type</span>
                <select v-model="uploadForm.type" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm">
                  <option v-for="type in documentTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                </select>
              </label>
              <label class="block">
                <span class="text-xs font-medium text-hris-muted">Issue date</span>
                <input v-model="uploadForm.issue_date" type="date" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
              </label>
            </div>
            <label class="block">
              <span class="text-xs font-medium text-hris-muted">Title</span>
              <input v-model="uploadForm.title" required class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
              <span v-if="fieldError('title')" class="mt-1 block text-xs text-red-600">{{ fieldError('title') }}</span>
            </label>
            <label class="block">
              <span class="text-xs font-medium text-hris-muted">File</span>
              <input class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" type="file" @change="onFileChange" />
              <span v-if="fieldError('file')" class="mt-1 block text-xs text-red-600">{{ fieldError('file') }}</span>
            </label>
            <label class="block">
              <span class="text-xs font-medium text-hris-muted">Notes</span>
              <textarea v-model="uploadForm.notes" rows="2" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"></textarea>
            </label>
            <button
              type="submit"
              class="rounded-md border border-hris-border px-4 py-2 text-sm font-semibold hover:bg-hris-surface disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="uploading"
            >
              {{ uploading ? 'Uploading...' : 'Upload' }}
            </button>
          </div>
        </form>
      </aside>
    </div>
  </section>
</template>
