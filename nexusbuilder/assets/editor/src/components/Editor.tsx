import React, { useEffect, useCallback } from 'react';
import { Toolbar } from './Toolbar/Toolbar';
import { LeftPanel } from './Panel/LeftPanel';
import { Canvas } from './Canvas/Canvas';
import { RightPanel } from './Panel/RightPanel';
import { AIPanel } from './AI/AIPanel';
import { HistoryPanel } from './History/HistoryPanel';
import { ContextMenu } from './Canvas/ContextMenu';
import { useUIStore } from '../store/ui';
import { useElementsStore } from '../store/elements';
import { useAutoSave } from '../hooks/useAutoSave';
import { useKeyboardShortcuts } from '../hooks/useKeyboardShortcuts';
import styles from './Editor.module.css';

interface EditorProps {
  config: typeof NexusBuilderConfig;
}

export const Editor: React.FC<EditorProps> = ({ config }) => {
  const { leftPanelOpen, rightPanelOpen, aiPanelOpen, historyPanelOpen } = useUIStore();
  const { loadPage } = useElementsStore();

  // Load saved page data on mount
  useEffect(() => {
    loadPage(config.postId, config.restUrl, config.nonce);
  }, [config.postId]);

  // Auto-save every 60 seconds if changes exist
  useAutoSave({ postId: config.postId, interval: 60_000 });

  // Register all keyboard shortcuts
  useKeyboardShortcuts();

  return (
    <div className={styles.editorRoot} data-theme="light">
      <Toolbar postId={config.postId} aiEnabled={config.aiEnabled} />

      <div className={styles.editorBody}>
        {leftPanelOpen && (
          <LeftPanel elementsSchema={config.elementsSchema} />
        )}

        <main className={styles.canvasArea}>
          <Canvas />
          <ContextMenu />
        </main>

        {rightPanelOpen && <RightPanel />}

        {aiPanelOpen && (
          <AIPanel postId={config.postId} brandContext={config.brandContext} />
        )}

        {historyPanelOpen && (
          <HistoryPanel postId={config.postId} />
        )}
      </div>
    </div>
  );
};
