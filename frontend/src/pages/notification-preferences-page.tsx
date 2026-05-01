import { useEffect, useState } from 'react';
import { Button } from '../components/ui/button';
import { Topbar } from '../components/layout/topbar';
import { useAuth } from '../features/auth/auth-context';
import { api } from '../lib/api';
import type { ApiPreference } from '../types';

interface PreferencesResponse {
  data: ApiPreference[];
}

type PreferenceKey = `${string}::${string}`;

function preferenceKey(type: string, channel: string): PreferenceKey {
  return `${type}::${channel}` as PreferenceKey;
}

export function NotificationPreferencesPage() {
  const { token } = useAuth();

  const [preferences, setPreferences] = useState<ApiPreference[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [saveSuccess, setSaveSuccess] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);

  useEffect(() => {
    async function fetchPreferences() {
      try {
        const response = await api.get<PreferencesResponse>(
          '/notification-preferences',
          token ?? undefined,
        );
        setPreferences(response.data);
      } finally {
        setIsLoading(false);
      }
    }
    fetchPreferences();
  }, [token]);

  function handleToggle(type: string, channel: string) {
    setPreferences((prev) =>
      prev.map((p) =>
        p.type === type && p.channel === channel
          ? { ...p, enabled: !p.enabled }
          : p,
      ),
    );
    setSaveSuccess(false);
    setSaveError(null);
  }

  async function handleSave() {
    setIsSaving(true);
    setSaveSuccess(false);
    setSaveError(null);

    try {
      await api.put(
        '/notification-preferences',
        {
          preferences: preferences.map(({ type, channel, enabled }) => ({
            type,
            channel,
            enabled,
          })),
        },
        token ?? undefined,
      );
      setSaveSuccess(true);
    } catch {
      setSaveError('Failed to save preferences. Please try again.');
    } finally {
      setIsSaving(false);
    }
  }

  // Group preferences by type
  const typeLabels = new Map<string, string>();
  const byType = new Map<string, ApiPreference[]>();

  for (const pref of preferences) {
    if (!byType.has(pref.type)) {
      byType.set(pref.type, []);
      typeLabels.set(pref.type, pref.type_label);
    }
    byType.get(pref.type)!.push(pref);
  }

  return (
    <div
      style={{
        maxWidth: 680,
        margin: '0 auto',
        backgroundColor: 'var(--color-bg-tertiary)',
        borderRadius: 'var(--radius-lg)',
        border: '0.5px solid var(--color-border-tertiary)',
        overflow: 'hidden',
      }}
    >
      <Topbar
        left={
          <span
            style={{
              fontFamily: 'var(--font-serif)',
              fontSize: 18,
              fontWeight: 500,
            }}
          >
            DevHub
          </span>
        }
        right={null}
      />

      <div style={{ padding: '24px 28px' }}>
        <div className="flex items-center justify-between mb-6">
          <div>
            <h1
              style={{
                fontFamily: 'var(--font-serif)',
                fontSize: 22,
                fontWeight: 500,
                margin: '0 0 4px',
              }}
            >
              Notification preferences
            </h1>
            <p
              style={{
                margin: 0,
                fontSize: 13,
                color: 'var(--color-text-secondary)',
              }}
            >
              Choose which notifications you receive and how.
            </p>
          </div>
          <Button
            variant="primary"
            onClick={handleSave}
            disabled={isSaving || isLoading}
          >
            {isSaving ? 'Saving…' : 'Save preferences'}
          </Button>
        </div>

        {saveSuccess && (
          <div
            style={{
              padding: '10px 14px',
              borderRadius: 'var(--radius-md)',
              backgroundColor: '#f0fdf4',
              border: '0.5px solid #bbf7d0',
              color: '#15803d',
              fontSize: 13,
              marginBottom: 16,
            }}
          >
            Preferences saved successfully.
          </div>
        )}

        {saveError && (
          <div
            style={{
              padding: '10px 14px',
              borderRadius: 'var(--radius-md)',
              backgroundColor: '#fef2f2',
              border: '0.5px solid #fecaca',
              color: '#dc2626',
              fontSize: 13,
              marginBottom: 16,
            }}
          >
            {saveError}
          </div>
        )}

        {isLoading ? (
          <div
            style={{
              textAlign: 'center',
              padding: '48px 0',
              color: 'var(--color-text-tertiary)',
              fontSize: 14,
            }}
          >
            Loading…
          </div>
        ) : preferences.length === 0 ? (
          <div
            style={{
              textAlign: 'center',
              padding: '48px 0',
              color: 'var(--color-text-tertiary)',
              fontSize: 14,
            }}
          >
            No notification preferences available.
          </div>
        ) : (
          <div className="flex flex-col gap-4">
            {Array.from(byType.entries()).map(([type, prefs]) => (
              <div
                key={type}
                style={{
                  backgroundColor: 'var(--color-bg-primary)',
                  borderRadius: 'var(--radius-md)',
                  border: '0.5px solid var(--color-border-tertiary)',
                  overflow: 'hidden',
                }}
              >
                <div
                  style={{
                    padding: '12px 16px',
                    borderBottom: '0.5px solid var(--color-border-tertiary)',
                  }}
                >
                  <p
                    style={{
                      margin: 0,
                      fontSize: 14,
                      fontWeight: 500,
                      color: 'var(--color-text-primary)',
                    }}
                  >
                    {typeLabels.get(type)}
                  </p>
                </div>

                {prefs.map((pref, index) => (
                  <div
                    key={preferenceKey(pref.type, pref.channel)}
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'space-between',
                      padding: '12px 16px',
                      borderBottom:
                        index < prefs.length - 1
                          ? '0.5px solid var(--color-border-tertiary)'
                          : 'none',
                    }}
                  >
                    <span
                      style={{
                        fontSize: 14,
                        color: 'var(--color-text-secondary)',
                      }}
                    >
                      {pref.channel_label}
                    </span>
                    <ToggleSwitch
                      checked={pref.enabled}
                      onChange={() => handleToggle(pref.type, pref.channel)}
                    />
                  </div>
                ))}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

interface ToggleSwitchProps {
  checked: boolean;
  onChange: () => void;
}

function ToggleSwitch({ checked, onChange }: ToggleSwitchProps) {
  return (
    <button
      role="switch"
      aria-checked={checked}
      onClick={onChange}
      style={{
        position: 'relative',
        display: 'inline-flex',
        alignItems: 'center',
        width: 36,
        height: 20,
        borderRadius: 10,
        backgroundColor: checked ? '#1a1a1a' : 'var(--color-bg-tertiary)',
        border: `1.5px solid ${checked ? '#1a1a1a' : 'var(--color-border-secondary)'}`,
        cursor: 'pointer',
        transition: 'background-color 0.2s, border-color 0.2s',
        flexShrink: 0,
      }}
    >
      <span
        style={{
          position: 'absolute',
          left: checked ? 18 : 2,
          width: 14,
          height: 14,
          borderRadius: '50%',
          backgroundColor: checked ? '#ffffff' : 'var(--color-text-tertiary)',
          transition: 'left 0.2s, background-color 0.2s',
        }}
      />
    </button>
  );
}
