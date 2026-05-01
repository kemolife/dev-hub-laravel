interface ProseContentProps {
  html: string;
}

export function ProseContent({ html }: ProseContentProps) {
  return (
    <div
      className="prose-content"
      style={{
        fontFamily: 'var(--font-serif)',
        fontSize: 18,
        lineHeight: 1.7,
      }}
      // TODO: sanitize html with DOMPurify before connecting to API — mock data only is safe
      dangerouslySetInnerHTML={{ __html: html }}
    />
  );
}
