import React, { useState, useRef, useEffect } from 'react';
import { useGlobalStylesStore } from '../../store/globalStyles';

interface Props {
  value: string;
  onChange: (value: string) => void;
  label: string;
}

export const ColorControl: React.FC<Props> = ({ value, onChange, label }) => {
  const [open, setOpen]     = useState(false);
  const [mode, setMode]     = useState<'solid'|'gradient'|'token'>('solid');
  const pickerRef           = useRef<HTMLDivElement>(null);
  const { colorTokens }     = useGlobalStylesStore();

  // Close picker on outside click
  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (pickerRef.current && !pickerRef.current.contains(e.target as Node)) {
        setOpen(false);
      }
    };
    if (open) document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, [open]);

  const isToken    = value?.startsWith('var(--');
  const isGradient = value?.includes('gradient');

  return (
    <div className="control-row">
      <label className="control-label">{label}</label>
      <div className="color-control">
        {/* Color swatch trigger */}
        <button
          className="color-swatch"
          style={{ background: value || 'transparent' }}
          onClick={() => setOpen(o => !o)}
          title={value || 'No color'}
        />
        <input
          type="text"
          className="color-hex-input"
          value={value || ''}
          onChange={e => onChange(e.target.value)}
          placeholder="#000000 or var(--)"
        />

        {open && (
          <div className="color-picker-popover" ref={pickerRef}>
            {/* Mode tabs */}
            <div className="color-mode-tabs">
              {(['solid','gradient','token'] as const).map(m => (
                <button
                  key={m}
                  className={mode === m ? 'active' : ''}
                  onClick={() => setMode(m)}
                >
                  {m.charAt(0).toUpperCase() + m.slice(1)}
                </button>
              ))}
            </div>

            {mode === 'solid' && (
              <>
                {/* Native color input as base */}
                <input
                  type="color"
                  value={value?.startsWith('#') ? value : '#000000'}
                  onChange={e => onChange(e.target.value)}
                  className="color-picker-native"
                />
                {/* Opacity slider */}
                <div className="opacity-row">
                  <span>Opacity</span>
                  <input type="range" min={0} max={100} defaultValue={100}
                    onChange={e => {
                      const hex = value?.slice(0,7) || '#000000';
                      const alpha = Math.round(Number(e.target.value) / 100 * 255).toString(16).padStart(2,'0');
                      onChange(hex + alpha);
                    }}
                  />
                </div>
              </>
            )}

            {mode === 'gradient' && (
              <GradientBuilder value={value} onChange={onChange} />
            )}

            {mode === 'token' && (
              <div className="token-list">
                {colorTokens.map(token => (
                  <button
                    key={token.id}
                    className="token-row"
                    onClick={() => { onChange(`var(--nexus-color-${token.id})`); setOpen(false); }}
                  >
                    <span className="token-swatch" style={{ background: token.value }} />
                    <span className="token-name">{token.name}</span>
                    <code className="token-var">--nexus-color-{token.id}</code>
                  </button>
                ))}
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
};
