// Minimal ambient typings for the browser-native BarcodeDetector API, which is
// not yet part of the standard TypeScript DOM lib. Used by the QR scanner with a
// runtime feature check + manual-entry fallback.

interface DetectedBarcode {
  rawValue: string
  format: string
  boundingBox: DOMRectReadOnly
}

interface BarcodeDetectorOptions {
  formats?: string[]
}

declare class BarcodeDetector {
  constructor(options?: BarcodeDetectorOptions)
  static getSupportedFormats(): Promise<string[]>
  detect(source: CanvasImageSource | Blob | ImageData): Promise<DetectedBarcode[]>
}

interface Window {
  BarcodeDetector?: typeof BarcodeDetector
}
