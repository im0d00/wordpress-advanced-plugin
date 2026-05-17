import React, { useState } from 'react';
import { useUIStore } from '../../store/ui';
import { useElementsStore } from '../../store/elements';
import { useTemporalStore } from '../../store/temporal';

interface Props {
  postId: number;
  aiEnabled: boolean;
}

export const Toolbar: React.FC<Props> = ({ postId, aiEnabled }) => {
  const { device, setDevice, zoom, setZoom, toggleLeftPanel, toggleRightPanel, status } = useUIStore();
  const { undo, redo, pastStates, futureStates } = useTemporalStore();
  const [menuOpen, setMenuOpen] = useState(false);

  const handleSave = () => {
    document.dispatchEvent(new CustomEvent('nexusbuilder:action:save'));
  };

  const handleExit = () => {
    window.location.href = `/wp-admin/post.php?post=${postId}&action=edit`;
  };

  const DEVICES = [
    { id: 'desktop', icon: 'ti-desktop', label: 'Desktop (1280px)' },
    { id: 'tablet',  icon: 'ti-tablet',  label: 'Tablet (768px)' },
    { id: 'mobile',  icon: 'ti-mobile',  label: 'Mobile (375px)' },
  ];

  return (
    <header className="nexus-toolbar">
      {/* ── LEFT: Menu & Undo ──────────────────────── */}
      <div className="nexus-toolbar-section">
        <div className="nexus-dropdown">
          <button className="nexus-btn-icon" onClick={() => setMenuOpen(!menuOpen)}>
            <i className="ti-menu"></i>
          </button>
          {menuOpen && (
            <div className="nexus-dropdown-menu">
              <button onClick={() => window.open(`/?p=${postId}&preview=true`, '_blank')}>
                <i className="ti-eye"></i> View Preview
              </button>
              <button onClick={() => useUIStore.getState().toggleHistoryPanel()}>
                <i className="ti-time"></i> Revision History
              </button>
              <hr />
              <button onClick={handleExit} className="text-danger">
                <i className="ti-arrow-left"></i> Exit to Default Editor
              </button>
            </div>
          )}
        </div>

        <button className="nexus-btn-toggle" onClick={toggleLeftPanel} title="Toggle Elements Panel">
          <i className="ti-layout-sidebar-left"></i>
        </button>

        <div className="nexus-divider"></div>

        <button className="nexus-btn-icon" onClick={undo} disabled={pastStates.length === 0} title="Undo (Ctrl+Z)">
          <i className="ti-back-left"></i>
        </button>
        <button className="nexus-btn-icon" onClick={redo} disabled={futureStates.length === 0} title="Redo (Ctrl+Y)">
          <i className="ti-back-right"></i>
        </button>
      </div>

      {/* ── CENTER: Device & Zoom ────────────────────── */}
      <div className="nexus-toolbar-section">
        <div className="nexus-device-toggle">
          {DEVICES.map(d => (
            <button
              key={d.id}
              className={device === d.id ? 'active' : ''}
              onClick={() => setDevice(d.id as any)}
              title={d.label}
            >
              <i className={d.icon}></i>
            </button>
          ))}
        </div>

        <div className="nexus-zoom-control">
          <button onClick={() => setZoom(Math.max(0.2, zoom - 0.1))}>-</button>
          <span onClick={() => setZoom(1)}>{Math.round(zoom * 100)}%</span>
          <button onClick={() => setZoom(Math.min(3, zoom + 0.1))}>+</button>
        </div>
      </div>

      {/* ── RIGHT: Actions & AI ──────────────────────── */}
      <div className="nexus-toolbar-section">
        {aiEnabled && (
          <button className="nexus-btn-ai" onClick={() => useUIStore.getState().toggleAIPanel()}>
            <i className="ti-wand"></i> AI Assistant
          </button>
        )}

        <button className="nexus-btn-toggle" onClick={toggleRightPanel} title="Toggle Properties Panel">
          <i className="ti-layout-sidebar-right"></i>
        </button>

        <div className="nexus-save-status">
          {status === 'saving' && <span className="saving"><i className="ti-reload spin"></i> Saving…</span>}
          {status === 'saved'  && <span className="saved"><i className="ti-check"></i> Saved</span>}
          {status === 'error'  && <span className="error"><i className="ti-alert"></i> Error auto-saving</span>}
        </div>

        <button className="nexus-btn-primary" onClick={handleSave}>
          Update
        </button>
      </div>
    </header>
  );
};
