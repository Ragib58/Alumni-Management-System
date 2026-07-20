import api from '@/lib/api'
import type {
  ApiResponse,
  Attendance,
  AttendanceStats,
  CheckInResult,
  Paginated,
} from '@/types'

export const attendanceService = {
  /**
   * Check in by scanned QR value (raw payload or bare token).
   * `eventId` optionally binds the scanner to one event.
   */
  async checkInByQr(qr: string, eventId?: number): Promise<CheckInResult> {
    const { data } = await api.post<ApiResponse<Attendance>>('/admin/attendance/check-in', {
      qr,
      event_id: eventId,
    })
    return { attendance: data.data, message: data.message, duplicate: !/success/i.test(data.message) }
  },

  /** Manual check-in by registration id. */
  async checkInByRegistration(registrationId: number, eventId?: number): Promise<CheckInResult> {
    const { data } = await api.post<ApiResponse<Attendance>>('/admin/attendance/check-in', {
      registration_id: registrationId,
      event_id: eventId,
    })
    return { attendance: data.data, message: data.message, duplicate: !/success/i.test(data.message) }
  },

  async checkOut(registrationId: number): Promise<Attendance> {
    const { data } = await api.post<ApiResponse<Attendance>>('/admin/attendance/check-out', {
      registration_id: registrationId,
    })
    return data.data
  },

  async listForEvent(
    eventId: number,
    params: { status?: string; search?: string; page?: number; per_page?: number } = {},
  ): Promise<Paginated<Attendance> & { stats: AttendanceStats }> {
    const { data } = await api.get<ApiResponse<Attendance[]>>(
      `/admin/events/${eventId}/attendance`,
      { params },
    )
    return {
      data: data.data,
      meta: data.meta!,
      stats: (data.meta as unknown as { stats: AttendanceStats }).stats,
    }
  },

  async stats(eventId: number): Promise<AttendanceStats> {
    const { data } = await api.get<ApiResponse<AttendanceStats>>(
      `/admin/events/${eventId}/attendance/stats`,
    )
    return data.data
  },
}
