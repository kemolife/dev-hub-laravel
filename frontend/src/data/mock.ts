import type { Author, Post } from '../types';

export const mockAuthors: Author[] = [
  {
    id: 'a1',
    name: 'Sara Rinaldi',
    initials: 'SR',
    avatarBg: '#e6f1fb',
    avatarColor: '#185fa5',
  },
  {
    id: 'a2',
    name: 'Daniel Kobayashi',
    initials: 'DK',
    avatarBg: '#EEEDFE',
    avatarColor: '#3C3489',
  },
  {
    id: 'a3',
    name: 'Anna Meijer',
    initials: 'AM',
    avatarBg: '#E1F5EE',
    avatarColor: '#085041',
  },
  {
    id: 'a4',
    name: 'Joseph Tan',
    initials: 'JT',
    avatarBg: '#FAECE7',
    avatarColor: '#712B13',
  },
];

export const mockTags: string[] = [
  'postgres',
  'rust',
  'distributed-systems',
  'debugging',
  'postmortem',
];

export const mockPosts: Post[] = [
  {
    id: 'p1',
    title:
      'Why we replaced Redis with Postgres for queues — and the three months it took to find out we were wrong',
    subtitle: 'And the three months it took to find out we were wrong',
    excerpt:
      'A long, honest postmortem from a team that chased simplicity into a corner. Includes the migration script, the metrics that lied to us, and what we shipped instead.',
    content: `<p>In late 2024 we made a decision that felt obvious at the time: we'd consolidate our infrastructure by moving our queue workload from Redis onto Postgres. One database, fewer services, simpler ops. The team agreed in twenty minutes.</p>
<p>What followed was three months of progressively worse production incidents, a rewrite of our worker layer, and a careful walk-back to a setup that, in hindsight, almost any senior engineer should have predicted.</p>
<p>This is the long version, with the metrics that misled us, the moment we knew, and the boring final answer.</p>
<h2>The setup we started with</h2>
<p>Our system processed about 8 million jobs a day across four queues: email delivery, webhook fan-out, search indexing, and a long-running ETL pipeline. Redis was holding all of it through a standard library setup...</p>
<pre><code>QUEUE_DRIVER=redis
REDIS_HOST=redis-primary.internal
REDIS_DB_QUEUE=2
QUEUE_RETRY_AFTER=90</code></pre>
<p>It worked well. So well, in fact, that we'd stopped paying attention to it — which is, I now understand, exactly the kind of system you should not move...</p>`,
    author: mockAuthors[0],
    publishedAt: new Date(Date.now() - 3 * 60 * 60 * 1000).toISOString(),
    readingMinutes: 22,
    wordCount: 4200,
    tags: ['postgres', 'infrastructure', 'postmortem'],
  },
  {
    id: 'p2',
    title:
      "A patient walk through Rust's borrow checker for people who already know it intellectually but still fight it daily",
    excerpt:
      'No clever metaphors. Just the rules, the failure modes, and the actual mental model that makes them stop feeling arbitrary.',
    content: `<p>There is a particular kind of frustration reserved for the Rust borrow checker. You understand the rules. You can explain them to someone else. And yet, three times a week, the compiler stops you with an error that feels wrong.</p>
<p>This is a walk through the rules as mental models, not definitions. It assumes you've read the book. It assumes you know what ownership means. What it offers is the frame that finally made the errors feel obvious rather than arbitrary.</p>
<h2>The single rule that explains everything</h2>
<p>At any given moment, for any piece of data, you can have either one mutable reference or any number of immutable references — but not both.</p>`,
    author: mockAuthors[1],
    publishedAt: new Date(Date.now() - 30 * 60 * 60 * 1000).toISOString(),
    readingMinutes: 14,
    wordCount: 2800,
    tags: ['rust', 'deep-dive'],
  },
  {
    id: 'p3',
    title:
      'Notes from running a Postgres database with 4 billion rows on a single machine for five years',
    excerpt:
      "What broke first, what surprised us, what we'd do the same again. Includes vacuum tuning that took us two years to land on.",
    content: `<p>Five years ago we made a bet: run our primary database on a single beefy machine instead of distributed infrastructure. The dataset was large. The queries were complex. The conventional wisdom said we were wrong.</p>
<p>We were not wrong. But we were surprised, repeatedly, by what actually broke and what held together better than expected.</p>
<h2>The machine</h2>
<p>We started with 64 cores, 512GB RAM, and 16TB of NVMe. The database grew from 800M rows to 4.1B rows over the period. At no point did we shard.</p>`,
    author: mockAuthors[2],
    publishedAt: new Date(Date.now() - 54 * 60 * 60 * 1000).toISOString(),
    readingMinutes: 31,
    wordCount: 6200,
    tags: ['postgres', 'scaling', 'war-stories'],
  },
  {
    id: 'p4',
    title: 'Building a small CRDT from scratch to finally understand what one is',
    excerpt:
      'Step-by-step construction with code, diagrams, and three failed attempts before the working version.',
    content: `<p>I spent two years nodding along whenever someone mentioned CRDTs. I'd read the Wikipedia article three times. I understood the acronym. I could not have implemented one.</p>
<p>This is the process of building one from scratch — not the cleanest version, but the version where I finally understood why the rules are what they are.</p>
<h2>What we're building</h2>
<p>A grow-only counter: the simplest possible CRDT. Two nodes. Each can increment independently. Both must eventually agree on the total.</p>`,
    author: mockAuthors[3],
    publishedAt: new Date(Date.now() - 78 * 60 * 60 * 1000).toISOString(),
    readingMinutes: 18,
    wordCount: 3600,
    tags: ['distributed-systems', 'tutorial'],
  },
];
