import { useRef, useState, useEffect, RefObject } from 'react';
import { useElementsStore } from '../store/elements';

interface Box { x: number; y: number; w: number; h: number; }

export function useMarqueeSelect(canvasRef: RefObject<HTMLElement>) {
  const [box, setBox]       = useState<Box | null>(null);
  const startRef            = useRef<{ x: number; y: number } | null>(null);
  const { selectElement, clearSelection } = useElementsStore();

  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;

    const onMouseDown = (e: MouseEvent) => {
      // Only start marquee if clicking directly on canvas background
      if ((e.target as HTMLElement) !== canvas) return;
      if (e.button !== 0) return;
      startRef.current = { x: e.clientX, y: e.clientY };
      clearSelection();
    };

    const onMouseMove = (e: MouseEvent) => {
      if (!startRef.current) return;
      const { x: sx, y: sy } = startRef.current;
      setBox({
        x: Math.min(e.clientX, sx),
        y: Math.min(e.clientY, sy),
        w: Math.abs(e.clientX - sx),
        h: Math.abs(e.clientY - sy),
      });
    };

    const onMouseUp = (e: MouseEvent) => {
      if (startRef.current && box) {
        // Find all elements whose bounding rect intersects the marquee box
        const elements = canvas.querySelectorAll<HTMLElement>('[data-element-id]');
        elements.forEach(el => {
          const r = el.getBoundingClientRect();
          if (rectsIntersect(box, { x: r.left, y: r.top, w: r.width, h: r.height })) {
            const id = el.dataset.elementId!;
            selectElement(id, true);
          }
        });
      }
      startRef.current = null;
      setBox(null);
    };

    canvas.addEventListener('mousedown', onMouseDown);
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup',   onMouseUp);

    return () => {
      canvas.removeEventListener('mousedown', onMouseDown);
      window.removeEventListener('mousemove', onMouseMove);
      window.removeEventListener('mouseup',   onMouseUp);
    };
  }, [box, clearSelection, selectElement, canvasRef]);

  return box;
}

function rectsIntersect(a: Box, b: Box): boolean {
  return !(a.x + a.w < b.x || b.x + b.w < a.x ||
           a.y + a.h < b.y || b.y + b.h < a.y);
}
