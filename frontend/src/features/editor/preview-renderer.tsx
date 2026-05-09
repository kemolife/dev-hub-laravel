import MarkdownIt from 'markdown-it';
import hljs from 'highlight.js';
import 'highlight.js/styles/github-dark.css';

const md: MarkdownIt = new MarkdownIt({
  html: false,
  linkify: true,
  typographer: true,
  highlight(str: string, lang: string): string {
    if (lang && hljs.getLanguage(lang)) {
      return `<pre class="hljs"><code>${hljs.highlight(str, { language: lang, ignoreIllegals: true }).value}</code></pre>`;
    }
    return `<pre class="hljs"><code>${md.utils.escapeHtml(str)}</code></pre>`;
  },
});

interface PreviewRendererProps {
  markdown: string;
}

export function PreviewRenderer({ markdown }: PreviewRendererProps) {
  return (
    <div
      className="prose-preview"
      style={{
        padding: '32px 48px',
        minHeight: 520,
        backgroundColor: 'var(--color-bg-primary)',
        fontFamily: 'var(--font-serif)',
        fontSize: 18,
        lineHeight: 1.7,
        color: 'var(--color-text-primary)',
      }}
      dangerouslySetInnerHTML={{ __html: md.render(markdown || '_Nothing to preview yet._') }}
    />
  );
}
