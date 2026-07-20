import api from '@/lib/api'
import type { ApiResponse, Paginated, Ticket } from '@/types'

const API_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api/v1'

export const ticketService = {
  async myTickets(page = 1, perPage = 10): Promise<Paginated<Ticket>> {
    const { data } = await api.get<ApiResponse<Ticket[]>>('/my-tickets', {
      params: { page, per_page: perPage },
    })
    return { data: data.data, meta: data.meta! }
  },

  async get(ticketId: number): Promise<Ticket> {
    const { data } = await api.get<ApiResponse<Ticket>>(`/tickets/${ticketId}`)
    return data.data
  },

  /**
   * Download the ticket PDF as a blob and trigger a browser save.
   */
  async download(ticketId: number, ticketNo: string): Promise<void> {
    const response = await api.get(`/tickets/${ticketId}/download`, {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data as BlobPart]))
    const link = document.createElement('a')
    link.href = url
    link.download = `${ticketNo}.pdf`
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  },

  /** Direct link to the PDF (auth via query is not supported; use download()). */
  downloadUrl(ticketId: number): string {
    return `${API_URL}/tickets/${ticketId}/download`
  },

  async email(ticketId: number): Promise<void> {
    await api.post(`/tickets/${ticketId}/email`)
  },
}
