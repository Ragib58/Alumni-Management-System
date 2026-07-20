import api from '@/lib/api'
import type {
  ApiResponse,
  InitiatePaymentResult,
  Paginated,
  Payment,
  PaymentFilters,
  PaymentGateway,
  RevenueStats,
} from '@/types'

function toParams(filters: PaymentFilters): Record<string, string | number> {
  const params: Record<string, string | number> = {}
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      params[key] = value as string | number
    }
  })
  return params
}

export const paymentService = {
  /** Step 2 — create a gateway session and get the redirect target. */
  async initiate(registrationId: number, gateway: PaymentGateway): Promise<InitiatePaymentResult> {
    const { data } = await api.post<ApiResponse<InitiatePaymentResult>>(
      `/registrations/${registrationId}/pay`,
      { gateway },
    )
    return data.data
  },

  /** Sandbox-mode completion from the simulated gateway page. */
  async sandboxComplete(
    paymentId: number,
    token: string,
    outcome: 'success' | 'failed',
  ): Promise<Payment> {
    const { data } = await api.post<ApiResponse<Payment>>(
      `/payments/${paymentId}/sandbox-complete`,
      { token, outcome },
    )
    return data.data
  },

  async get(paymentId: number): Promise<Payment> {
    const { data } = await api.get<ApiResponse<Payment>>(`/payments/${paymentId}`)
    return data.data
  },

  async myPayments(filters: PaymentFilters = {}): Promise<Paginated<Payment>> {
    const { data } = await api.get<ApiResponse<Payment[]>>('/my-payments', {
      params: toParams(filters),
    })
    return { data: data.data, meta: data.meta! }
  },

  // ---- Admin ----
  async adminList(filters: PaymentFilters = {}): Promise<Paginated<Payment>> {
    const { data } = await api.get<ApiResponse<Payment[]>>('/admin/payments', {
      params: toParams(filters),
    })
    return { data: data.data, meta: data.meta! }
  },

  async adminGet(paymentId: number): Promise<Payment> {
    const { data } = await api.get<ApiResponse<Payment>>(`/admin/payments/${paymentId}`)
    return data.data
  },

  async revenue(): Promise<RevenueStats> {
    const { data } = await api.get<ApiResponse<RevenueStats>>('/admin/payments-revenue')
    return data.data
  },

  async refund(paymentId: number): Promise<Payment> {
    const { data } = await api.post<ApiResponse<Payment>>(`/admin/payments/${paymentId}/refund`)
    return data.data
  },
}
