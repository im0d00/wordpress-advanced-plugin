import { useRef, useState, useCallback, useEffect, RefObject } from 'react';
import { useUIStore } from '../store/ui';

export function useCanvasPan(ref: RefObject<HTMLElement>) {
  const [offset, setOffset]   = useState({ x: 0, y: 0 });
  const [panning, setPanning] = useState(false);
  const startRef = useRef<{ x: number; y: number; ox: number; oy: number } | null>(null);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;

    const onMouseDown = (e: MouseEvent) => {
      // Spacebar held + left mouse = pan
      if (!useUIStore.getState().spaceHeld) return;
      e.preventDefault();
      setPanning(true);
      startRef.current = { x: e.clientX, y: e.clientY, ox: offset.x, oy: offset.y };
    };

    const onMouseMove = (e: MouseEvent) => {
      if (!panning || !startRef.current) return;
      setOffset({
        x: startRef.current.ox + (e.clientX - startRef.current.x),
        y: startRef.current.oy + (e.clientY - startRef.current.y),
      });
    };

    const onMouseUp = () => {
      setPanning(false);
      startRef.current = null;
    };

    el.addEventListener('mousedown', onMouseDown);
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);

    return () => {
      el.removeEventListener('mousedown', onMouseDown);
      window.removeEventListener('mousemove', onMouseMove);
      window.removeEventListener('mouseup', onMouseUp);
    };
  }, [panning, offset, ref]);

  // Track spacebar state for pan mode
  useEffect(() => {
    const dn = (e: KeyboardEvent) => {
      if (e.code === 'Space' && e.target === document.body) {
        e.preventDefault();
        useUIStore.getState().setSpaceHeld(true);
      }
    };
    const up = (e: KeyboardEvent) => {
      if (e.code === 'Space') useUIStore.getState().setSpaceHeld(false);
    };
    window.addEventListener('keydown', dn);
    window.addEventListener('keyup',   up);
    return () => { window.removeEventListener('keydown', dn); window.removeEventListener('keyup', up); };
  }, []);

  return { panOffset: offset, isPanning: panning, setPanOffset: setOffset };
}
