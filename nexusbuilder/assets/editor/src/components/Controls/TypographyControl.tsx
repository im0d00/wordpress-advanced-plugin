import React, { useState } from 'react';

const FONT_WEIGHTS = [100,200,300,400,500,600,700,800,900];
const TRANSFORMS   = ['none','uppercase','lowercase','capitalize'];
const DECORATIONS  = ['none','underline','line-through','overline'];
const DISPLAYS     = ['block','inline-block','inline'];

interface TypographyValue {
  fontFamily?:   string;
  size?:         string;
  weight?:       number;
  lineHeight?:   string;
  letterSpacing?: string;
  transform?:    string;
  decoration?:   string;
  fontStyle?:    string;
}

interface Props {
  value: TypographyValue;
  onChange: (v: TypographyValue) => void;
}

export const TypographyControl: React.FC<Props> = ({ value = {}, onChange }) => {
  const [open, setOpen] = useState(false);
  const patch = (k: keyof TypographyValue, v: any) => onChange({ ...value, [k]: v });

  const preview = [
    value.fontFamily || 'Default',
    value.size        || '',
    value.weight ? `${value.weight}` : '',
  ].filter(Boolean).join(' · ');

  return (
    <div className="control-row typography-control">
      <label className="control-label">Typography</label>
      <button className="typography-trigger" onClick={() => setOpen(o => !o)}>
        {preview || 'Set typography…'}
      </button>

      {open && (
        <div className="typography-popover">
          {/* Font family */}
          <div className="typo-row">
            <span>Font</span>
            <FontFamilyPicker
              value={value.fontFamily || ''}
              onChange={v => patch('fontFamily', v)}
            />
          </div>

          {/* Size + unit */}
          <div className="typo-row">
            <span>Size</span>
            <input type="number" min={0} step={1}
              value={parseInt(value.size || '16')}
              onChange={e => patch('size', e.target.value + 'px')}
            />
            <select defaultValue="px">
              {['px','rem','em','vw','%','clamp'].map(u => <option key={u}>{u}</option>)}
            </select>
          </div>

          {/* Weight */}
          <div className="typo-row">
            <span>Weight</span>
            <select value={value.weight || 400} onChange={e => patch('weight', Number(e.target.value))}>
              {FONT_WEIGHTS.map(w => <option key={w} value={w}>{w}</option>)}
            </select>
          </div>

          {/* Line height */}
          <div className="typo-row">
            <span>Line height</span>
            <input type="number" step={0.1} min={0}
              value={parseFloat(value.lineHeight || '1.5')}
              onChange={e => patch('lineHeight', e.target.value)}
            />
          </div>

          {/* Letter spacing */}
          <div className="typo-row">
            <span>Letter spacing</span>
            <input type="number" step={0.01}
              value={parseFloat(value.letterSpacing || '0')}
              onChange={e => patch('letterSpacing', e.target.value + 'em')}
            />
          </div>

          {/* Transform */}
          <div className="typo-row">
            <span>Transform</span>
            <div className="choose-group">
              {TRANSFORMS.map(t => (
                <button key={t}
                  className={value.transform === t ? 'active' : ''}
                  onClick={() => patch('transform', t)}
                  title={t}
                >{t === 'none' ? '—' : t === 'uppercase' ? 'AA' : t === 'lowercase' ? 'aa' : 'Aa'}</button>
              ))}
            </div>
          </div>

          {/* Italic toggle */}
          <div className="typo-row">
            <span>Style</span>
            <button
              className={value.fontStyle === 'italic' ? 'active' : ''}
              onClick={() => patch('fontStyle', value.fontStyle === 'italic' ? 'normal' : 'italic')}
            ><em>I</em></button>
          </div>
        </div>
      )}
    </div>
  );
};
