import api from '@/lib/api'
import type {
  AlumniReport,
  ApiResponse,
  EventReportRow,
  ExportFormat,
  FinancialReport,
  ReportType,
} from '@/types'

export const reportService = {
  async event(eventId?: number): Promise<EventReportRow[]> {
    const { data } = await api.get<ApiResponse<EventReportRow[]>>('/admin/reports/event', {
      params: eventId ? { event_id: eventId } : {},
    })
    return data.data
  },

  async financial(params: { status?: string; gateway?: string; event_id?: number } = {}): Promise<FinancialReport> {
    const { data } = await api.get<ApiResponse<FinancialReport>>('/admin/reports/financial', {
      params,
    })
    return data.data
  },

  async alumni(): Promise<AlumniReport> {
    const { data } = await api.get<ApiResponse<AlumniReport>>('/admin/reports/alumni')
    return data.data
  },

  /**
   * Download a report export as a file (blob) and trigger a browser save.
   */
  async export(
    type: ReportType,
    format: ExportFormat,
    params: Record<string, string | number> = {},
  ): Promise<void> {
    const response = await api.get(`/admin/reports/${type}/export/${format}`, {
      params,
      responseType: 'blob',
    })
    const ext = format === 'excel' ? 'xlsx' : format
    const url = window.URL.createObjectURL(new Blob([response.data as BlobPart]))
    const link = document.createElement('a')
    link.href = url
    link.download = `${type}-report.${ext}`
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  },
}
