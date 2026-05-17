import React, { useState } from 'react';
import { useElementsStore } from '../../store/elements';

const ANIMATION_TYPES = [
  { value: 'none',         label: 'None' },
  { value: 'fade-in',      label: 'Fade in' },
  { value: 'fade-in-up',   label: 'Fade in up' },
  { value: 'fade-in-down', label: 'Fade in down' },
  { value: 'fade-in-left', label: 'Fade in from left' },
  { value: 'fade-in-right', label: 'Fade in from right' },
  { value: 'zoom-in',      label: 'Zoom in' },
  { value: 'zoom-out',     label: 'Zoom out' },
  { value: 'flip-x',       label: 'Flip horizontal' },
  { value: 'flip-y',       label: 'Flip vertical' },
  { value: 'blur-in',      label: 'Blur in' },
  { value: 'bounce-in',    label: 'Bounce in' },
  { value: 'rotate-in',    label: 'Rotate in' },
  { value: 'stagger',      label: 'Stagger children' },
];

const EASING_OPTIONS = [
  'power1.out','power2.out','power3.out','power4.out',
  'back.out(1.7)','elastic.out(1,0.5)','bounce.out',
  'circ.out','expo.out','sine.out','linear',
];

const TRIGGER_OPTIONS = [
  { value: 'viewport',       label: 'On enter viewport' },
  { value: 'load',           label: 'On page load' },
  { value: 'scroll-position', label: 'On scroll position' },
  { value: 'hover',          label: 'On hover' },
  { value: 'click',          label: 'On click' },
];

export const MotionPanel: React.FC<{ nodeId: string }> = ({ nodeId }) => {
  const { getElement, updateElement } = useElementsStore();
  const node     = getElement(nodeId);
  const anim     = node?.settings?.animation ?? {};

  const update = (patch: Record<string, any>) =>
    updateElement(nodeId, { animation: { ...anim, ...patch } });

  return (
    <div className="motion-panel">
      <h3 className="section-heading">Entrance animation</h3>

      <div className="control-row">
        <label>Type</label>
        <select value={anim.type || 'none'} onChange={e => update({ type: e.target.value })}>
          {ANIMATION_TYPES.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
        </select>
      </div>

      {anim.type && anim.type !== 'none' && (
        <>
          <div className="control-row">
            <label>Trigger</label>
            <select value={anim.trigger || 'viewport'} onChange={e => update({ trigger: e.target.value })}>
              {TRIGGER_OPTIONS.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
            </select>
          </div>

          <div className="control-row">
            <label>Duration</label>
            <input type="range" min={0.1} max={3} step={0.05}
              value={anim.duration || 0.8}
              onChange={e => update({ duration: Number(e.target.value) })}
            />
            <span>{(anim.duration || 0.8).toFixed(2)}s</span>
          </div>

          <div className="control-row">
            <label>Delay</label>
            <input type="range" min={0} max={3} step={0.05}
              value={anim.delay || 0}
              onChange={e => update({ delay: Number(e.target.value) })}
            />
            <span>{(anim.delay || 0).toFixed(2)}s</span>
          </div>

          <div className="control-row">
            <label>Easing</label>
            <select value={anim.ease || 'power2.out'} onChange={e => update({ ease: e.target.value })}>
              {EASING_OPTIONS.map(e => <option key={e}>{e}</option>)}
            </select>
          </div>

          {anim.type === 'stagger' && (
            <div className="control-row">
              <label>Stagger delay</label>
              <input type="range" min={0.02} max={0.5} step={0.02}
                value={anim.stagger || 0.1}
                onChange={e => update({ stagger: Number(e.target.value) })}
              />
              <span>{(anim.stagger || 0.1).toFixed(2)}s</span>
            </div>
          )}

          <div className="control-row">
            <label>Repeat</label>
            <input type="number" min={0} max={99} value={anim.repeat || 0}
              onChange={e => update({ repeat: Number(e.target.value) })}
            />
            <span className="hint">0 = play once, -1 = infinite</span>
          </div>

          {/* Preview button */}
          <button className="preview-animation-btn"
            onClick={() => {
              const el = document.querySelector(`[data-element-id="${nodeId}"]`) as HTMLElement;
              if (el) {
                el.style.animation = 'none';
                setTimeout(() => { el.style.animation = ''; }, 10);
              }
            }}
          >
            ▶ Preview in canvas
          </button>
        </>
      )}

      <hr />
      <h3 className="section-heading">Scroll parallax</h3>

      <div className="control-row">
        <label>Enable parallax</label>
        <input type="checkbox"
          checked={!!node?.settings?.parallax?.enabled}
          onChange={e => updateElement(nodeId, {
            parallax: { ...(node?.settings?.parallax ?? {}), enabled: e.target.checked }
          })}
        />
      </div>

      {node?.settings?.parallax?.enabled && (
        <div className="control-row">
          <label>Speed</label>
          <input type="range" min={-2} max={2} step={0.1}
            value={node?.settings?.parallax?.speed ?? 0.5}
            onChange={e => updateElement(nodeId, {
              parallax: { ...(node?.settings?.parallax ?? {}), speed: Number(e.target.value) }
            })}
          />
          <span>{node?.settings?.parallax?.speed ?? 0.5}</span>
        </div>
      )}

      <hr />
      <h3 className="section-heading">Magnetic effect</h3>
      <div className="control-row">
        <label>Enable magnetic</label>
        <input type="checkbox"
          checked={!!node?.settings?.magnetic}
          onChange={e => updateElement(nodeId, { magnetic: e.target.checked })}
        />
      </div>
    </div>
  );
};
