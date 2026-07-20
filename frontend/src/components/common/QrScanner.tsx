import { useEffect, useRef, useState } from 'react'
import { Camera, CameraOff } from 'lucide-react'
import { Button } from '@/components/ui/button'

interface QrScannerProps {
  /** Fired once per successful decode (debounced against repeats). */
  onScan: (value: string) => void
  active: boolean
}

/**
 * Camera QR scanner built on the native BarcodeDetector API. Falls back to a
 * message when the browser/device can't provide it (use manual entry instead).
 */
export function QrScanner({ onScan, active }: QrScannerProps) {
  const videoRef = useRef<HTMLVideoElement>(null)
  const streamRef = useRef<MediaStream | null>(null)
  const rafRef = useRef<number | null>(null)
  const lastValueRef = useRef<string>('')
  const lastTimeRef = useRef<number>(0)

  const [supported] = useState<boolean>(
    typeof window !== 'undefined' && 'BarcodeDetector' in window,
  )
  const [error, setError] = useState<string | null>(null)
  const [running, setRunning] = useState(false)

  useEffect(() => {
    if (!active || !supported) return

    let cancelled = false
    const detector = new window.BarcodeDetector!({ formats: ['qr_code'] })

    const start = async () => {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: 'environment' },
        })
        if (cancelled) {
          stream.getTracks().forEach((t) => t.stop())
          return
        }
        streamRef.current = stream
        if (videoRef.current) {
          videoRef.current.srcObject = stream
          await videoRef.current.play()
        }
        setRunning(true)
        tick(detector)
      } catch {
        setError('Unable to access the camera. Grant permission or use manual entry.')
      }
    }

    void start()

    return () => {
      cancelled = true
      stop()
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [active, supported])

  const tick = (detector: BarcodeDetector) => {
    const scan = async () => {
      if (!videoRef.current || videoRef.current.readyState < 2) {
        rafRef.current = requestAnimationFrame(scan)
        return
      }
      try {
        const codes = await detector.detect(videoRef.current)
        if (codes.length > 0) {
          const value = codes[0].rawValue
          const now = Date.now()
          // Debounce identical scans within 2.5s.
          if (value !== lastValueRef.current || now - lastTimeRef.current > 2500) {
            lastValueRef.current = value
            lastTimeRef.current = now
            onScan(value)
          }
        }
      } catch {
        // transient detect errors are ignored
      }
      rafRef.current = requestAnimationFrame(scan)
    }
    rafRef.current = requestAnimationFrame(scan)
  }

  const stop = () => {
    if (rafRef.current) cancelAnimationFrame(rafRef.current)
    streamRef.current?.getTracks().forEach((t) => t.stop())
    streamRef.current = null
    setRunning(false)
  }

  if (!supported) {
    return (
      <div className="flex flex-col items-center gap-2 rounded-lg border border-dashed p-8 text-center">
        <CameraOff className="h-8 w-8 text-muted-foreground" />
        <p className="text-sm text-muted-foreground">
          Camera scanning isn&apos;t supported in this browser. Use manual entry below.
        </p>
      </div>
    )
  }

  return (
    <div className="space-y-3">
      <div className="relative overflow-hidden rounded-lg bg-black">
        <video ref={videoRef} className="aspect-video w-full object-cover" muted playsInline />
        {/* Scan reticle */}
        <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
          <div className="h-40 w-40 rounded-lg border-2 border-white/80 shadow-[0_0_0_9999px_rgba(0,0,0,0.35)]" />
        </div>
      </div>
      {error ? (
        <p className="text-center text-sm text-destructive">{error}</p>
      ) : (
        <p className="flex items-center justify-center gap-2 text-center text-sm text-muted-foreground">
          <Camera className="h-4 w-4" />
          {running ? 'Point the camera at a ticket QR code.' : 'Starting camera…'}
        </p>
      )}
      {running && (
        <div className="text-center">
          <Button type="button" variant="ghost" size="sm" onClick={stop}>
            Stop camera
          </Button>
        </div>
      )}
    </div>
  )
}
