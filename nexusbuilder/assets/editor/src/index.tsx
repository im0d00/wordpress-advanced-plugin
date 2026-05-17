import React from 'react';
import { createRoot } from 'react-dom/client';
import { Editor } from './components/Editor';
import { initializeStores } from './store';
import './styles/editor.css';

// Data injected from PHP via wp_localize_script
declare const NexusBuilderConfig: {
  postId:       number;
  restUrl:      string;
  nonce:        string;
  homeUrl:      string;
  elementsSchema: ElementSchema[];
  globalStyles: GlobalStyles;
  breakpoints:  Breakpoint[];
  userCan:      Record<string, boolean>;
  aiEnabled:    boolean;
  version:      string;
};

async function boot() {
  const container = document.getElementById('nexusbuilder-editor');
  if (!container) return;

  // Initialize all Zustand stores with server data
  await initializeStores({
    postId:      NexusBuilderConfig.postId,
    restUrl:     NexusBuilderConfig.restUrl,
    nonce:       NexusBuilderConfig.nonce,
    globalStyles: NexusBuilderConfig.globalStyles,
    breakpoints:  NexusBuilderConfig.breakpoints,
  });

  const root = createRoot(container);
  root.render(
    <React.StrictMode>
      <Editor config={NexusBuilderConfig} />
    </React.StrictMode>
  );
}

boot();
