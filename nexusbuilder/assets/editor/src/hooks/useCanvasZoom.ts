import { useCallback } from 'react';
import { useUIStore } from '../store/ui';

export function useCanvasZoom() {
  const { zoom, setZoom } = useUIStore();

  const handleWheel = useCallback((e: React.WheelEvent) => {
    if (!e.ctrlKey && !e.metaKey) return;
    e.preventDefault();
    const delta   = e.deltaY < 0 ? 0.05 : -0.05;
    const newZoom = Math.min(3, Math.max(0.2, zoom + delta));
    setZoom(parseFloat(newZoom.toFixed(2)));
  }, [zoom, setZoom]);

  return { handleWheel };
}
