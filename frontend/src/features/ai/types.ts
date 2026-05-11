export type StreamChunk =
  | { type: 'content'; content: string }
  | { type: 'done'; conversationId?: string };
