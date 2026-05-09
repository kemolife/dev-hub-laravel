import { useEffect, useRef } from 'react';
import { EditorView, ViewPlugin, Decoration, keymap } from '@codemirror/view';
import type { DecorationSet, ViewUpdate } from '@codemirror/view';
import { EditorState, RangeSetBuilder } from '@codemirror/state';
import { markdown, markdownLanguage } from '@codemirror/lang-markdown';
import { languages } from '@codemirror/language-data';
import { defaultKeymap, history, historyKeymap } from '@codemirror/commands';
import { HighlightStyle, syntaxHighlighting, defaultHighlightStyle, syntaxTree } from '@codemirror/language';
import { tags } from '@lezer/highlight';
import { EditorToolbar } from './editor-toolbar';
import { PreviewRenderer } from './preview-renderer';

// Inline code gets a pill background; fenced block content uses GitHub-light syntax colors.
const codeStyle = HighlightStyle.define([
  { tag: tags.monospace, backgroundColor: 'rgba(175,184,193,0.25)', padding: '1px 5px', borderRadius: '4px' },
  { tag: tags.processingInstruction, color: '#6e7781' },
]);

const blockLineMark = Decoration.line({ class: 'cm-fenced-block' });

function buildCodeBlockDecorations(view: EditorView): DecorationSet {
  const builder = new RangeSetBuilder<Decoration>();
  syntaxTree(view.state).iterate({
    from: view.viewport.from,
    to: view.viewport.to,
    enter(node) {
      if (node.name === 'FencedCode') {
        const from = view.state.doc.lineAt(node.from);
        const to = view.state.doc.lineAt(node.to);
        for (let n = from.number; n <= to.number; n++) {
          const line = view.state.doc.line(n);
          builder.add(line.from, line.from, blockLineMark);
        }
        return false;
      }
    },
  });
  return builder.finish();
}

const codeBlockHighlighter = ViewPlugin.fromClass(
  class {
    decorations: DecorationSet;
    constructor(view: EditorView) { this.decorations = buildCodeBlockDecorations(view); }
    update(update: ViewUpdate) {
      if (update.docChanged || update.viewportChanged) {
        this.decorations = buildCodeBlockDecorations(update.view);
      }
    }
  },
  { decorations: (v) => v.decorations },
);

interface MarkdownEditorProps {
  title: string;
  subtitle: string;
  value: string;
  mode: 'write' | 'preview';
  onTitleChange: (value: string) => void;
  onSubtitleChange: (value: string) => void;
  onChange: (value: string) => void;
}

export function MarkdownEditor({
  title,
  subtitle,
  value,
  mode,
  onTitleChange,
  onSubtitleChange,
  onChange,
}: MarkdownEditorProps) {
  const editorRef = useRef<HTMLDivElement>(null);
  const viewRef = useRef<EditorView | null>(null);


  useEffect(() => {
    if (!editorRef.current) return;

    const state = EditorState.create({
      doc: value,
      extensions: [
        markdown({ base: markdownLanguage, codeLanguages: languages }),
        syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
        syntaxHighlighting(codeStyle),
        codeBlockHighlighter,
        history(),
        EditorView.lineWrapping,
        keymap.of([...defaultKeymap, ...historyKeymap]),
        EditorView.updateListener.of((update) => {
          if (update.docChanged) {
            onChange(update.state.doc.toString());
          }
        }),
        EditorView.theme({
          '&': {
            fontSize: '16px',
            fontFamily: 'var(--font-mono, monospace)',
            height: 'calc(100vh - 280px)',
          },
          '.cm-scroller': { overflow: 'auto' },
          '.cm-content': { padding: '16px 0', minHeight: '100%' },
          '.cm-focused': { outline: 'none' },
          '&, .cm-editor': { backgroundColor: 'var(--color-bg-primary)' },
          '.cm-gutters': { backgroundColor: 'var(--color-bg-primary)', border: 'none' },
          '&.cm-focused .cm-cursor': { borderLeftColor: 'var(--color-text-primary)' },
          '.cm-activeLine': { backgroundColor: 'transparent' },
          '.cm-fenced-block': { backgroundColor: 'rgba(175,184,193,0.15)', display: 'block' },
          '.cm-selectionBackground, ::selection': { backgroundColor: 'rgba(100,149,237,0.2)' },
        }),
      ],
    });

    const view = new EditorView({ state, parent: editorRef.current });
    viewRef.current = view;

    return () => {
      view.destroy();
      viewRef.current = null;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []); // intentionally run once — value sync handled below

  // Sync external value changes (e.g. loading an existing draft) without re-creating the editor
  useEffect(() => {
    const view = viewRef.current;
    if (!view) return;
    const current = view.state.doc.toString();
    if (current !== value) {
      view.dispatch({
        changes: { from: 0, to: current.length, insert: value },
      });
    }
  }, [value]);

  return (
    <div style={{ backgroundColor: 'var(--color-bg-primary)' }}>
      {/* Title + subtitle always visible */}
      <div style={{ padding: '32px 48px 0' }}>
        <input
          type="text"
          placeholder="Title"
          value={title}
          onChange={(e) => onTitleChange(e.target.value)}
          style={{
            width: '100%',
            border: 'none',
            background: 'transparent',
            fontSize: 26,
            fontWeight: 500,
            padding: 0,
            margin: '0 0 8px',
            lineHeight: 1.3,
            outline: 'none',
            fontFamily: 'inherit',
            color: 'var(--color-text-primary)',
          }}
        />
        <input
          type="text"
          placeholder="A short subtitle (optional)"
          value={subtitle}
          onChange={(e) => onSubtitleChange(e.target.value)}
          style={{
            width: '100%',
            border: 'none',
            background: 'transparent',
            fontSize: 16,
            padding: 0,
            margin: '0 0 16px',
            color: 'var(--color-text-secondary)',
            outline: 'none',
            fontFamily: 'inherit',
          }}
        />
        <div style={{ borderTop: '0.5px solid var(--color-border-tertiary)' }} />
      </div>

      {mode === 'write' ? (
        <>
          <EditorToolbar viewRef={viewRef} />
          <div ref={editorRef} style={{ padding: '0 48px' }} />
        </>
      ) : (
        <PreviewRenderer markdown={value} />
      )}
    </div>
  );
}
