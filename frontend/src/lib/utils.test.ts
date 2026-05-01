import { describe, expect, it } from 'vitest';
import { cn, readingTime, relativeTime, wordCount } from './utils';

describe('cn', () => {
  it('merges class names', () => {
    expect(cn('foo', 'bar')).toBe('foo bar');
  });

  it('resolves tailwind conflicts, keeping last', () => {
    expect(cn('p-2', 'p-4')).toBe('p-4');
  });
});

describe('readingTime', () => {
  it('returns 1 for exactly 200 words', () => {
    expect(readingTime(200)).toBe(1);
  });

  it('rounds up for partial pages', () => {
    expect(readingTime(201)).toBe(2);
  });

  it('returns 0 for 0 words', () => {
    expect(readingTime(0)).toBe(0);
  });
});

describe('wordCount', () => {
  it('counts words', () => {
    expect(wordCount('hello world foo')).toBe(3);
  });

  it('handles extra whitespace', () => {
    expect(wordCount('  hello   world  ')).toBe(2);
  });

  it('returns 0 for empty string', () => {
    expect(wordCount('')).toBe(0);
  });

  it('returns 0 for whitespace-only string', () => {
    expect(wordCount('   ')).toBe(0);
  });
});

describe('relativeTime', () => {
  it('returns "just now" for current time', () => {
    expect(relativeTime(new Date().toISOString())).toBe('just now');
  });

  it('returns hours ago for same-day times', () => {
    const twoHoursAgo = new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString();
    expect(relativeTime(twoHoursAgo)).toBe('2 hours ago');
  });

  it('uses singular for 1 hour', () => {
    const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000).toISOString();
    expect(relativeTime(oneHourAgo)).toBe('1 hour ago');
  });

  it('returns "yesterday" for ~25 hours ago', () => {
    const yesterday = new Date(Date.now() - 25 * 60 * 60 * 1000).toISOString();
    expect(relativeTime(yesterday)).toBe('yesterday');
  });

  it('returns "N days ago" for 2-6 days', () => {
    const twoDaysAgo = new Date(Date.now() - 50 * 60 * 60 * 1000).toISOString();
    expect(relativeTime(twoDaysAgo)).toBe('2 days ago');
  });
});
