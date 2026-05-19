import { useCallback, useEffect, useState } from 'react';
import { api, ApiError } from '../../lib/api';
import type { BillingStatus, Invoice } from '../../types';

export interface UseBillingReturn {
  billing: BillingStatus | null;
  invoices: Invoice[];
  isLoadingBilling: boolean;
  isLoadingInvoices: boolean;
  actionError: string | null;
  checkoutLoading: string | null;
  isCancelLoading: boolean;
  isResumeLoading: boolean;
  showCancelConfirm: boolean;
  setShowCancelConfirm: (show: boolean) => void;
  handleCheckout: (plan: 'pro' | 'pro_annual') => Promise<void>;
  handleCancel: () => Promise<void>;
  handleResume: () => Promise<void>;
  handleDownloadInvoice: (downloadUrl: string, invoiceId: string) => Promise<void>;
}

export function useBilling(token: string | null): UseBillingReturn {
  const [billing, setBilling] = useState<BillingStatus | null>(null);
  const [invoices, setInvoices] = useState<Invoice[]>([]);
  const [isLoadingBilling, setIsLoadingBilling] = useState(true);
  const [isLoadingInvoices, setIsLoadingInvoices] = useState(true);
  const [actionError, setActionError] = useState<string | null>(null);
  const [checkoutLoading, setCheckoutLoading] = useState<string | null>(null);
  const [isCancelLoading, setIsCancelLoading] = useState(false);
  const [isResumeLoading, setIsResumeLoading] = useState(false);
  const [showCancelConfirm, setShowCancelConfirm] = useState(false);

  const fetchBilling = useCallback(async () => {
    if (!token) return;
    try {
      const data = await api.get<BillingStatus>('/billing', token);
      setBilling(data);
    } finally {
      setIsLoadingBilling(false);
    }
  }, [token]);

  const fetchInvoices = useCallback(async () => {
    if (!token) return;
    try {
      const data = await api.get<Invoice[]>('/billing/invoices', token);
      setInvoices(data);
    } finally {
      setIsLoadingInvoices(false);
    }
  }, [token]);

  useEffect(() => {
    void fetchBilling();
    void fetchInvoices();
  }, [fetchBilling, fetchInvoices]);

  async function handleCheckout(plan: 'pro' | 'pro_annual'): Promise<void> {
    if (!token) return;
    setActionError(null);
    setCheckoutLoading(plan);
    try {
      const { url } = await api.post<{ url: string }>('/billing/checkout', { plan }, token);
      window.location.href = url;
    } catch (err) {
      setActionError(err instanceof ApiError ? err.message : 'Something went wrong.');
      setCheckoutLoading(null);
    }
  }

  async function handleCancel(): Promise<void> {
    if (!token) return;
    setActionError(null);
    setIsCancelLoading(true);
    try {
      await api.post('/billing/cancel', {}, token);
      setShowCancelConfirm(false);
      await fetchBilling();
    } catch (err) {
      setActionError(err instanceof ApiError ? err.message : 'Something went wrong.');
    } finally {
      setIsCancelLoading(false);
    }
  }

  async function handleResume(): Promise<void> {
    if (!token) return;
    setActionError(null);
    setIsResumeLoading(true);
    try {
      await api.post('/billing/resume', {}, token);
      await fetchBilling();
    } catch (err) {
      setActionError(err instanceof ApiError ? err.message : 'Something went wrong.');
    } finally {
      setIsResumeLoading(false);
    }
  }

  async function handleDownloadInvoice(downloadUrl: string, invoiceId: string): Promise<void> {
    if (!token) return;
    const response = await fetch(downloadUrl, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/pdf' },
    });
    const blob = await response.blob();
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `invoice-${invoiceId}.pdf`;
    a.click();
    URL.revokeObjectURL(url);
  }

  return {
    billing,
    invoices,
    isLoadingBilling,
    isLoadingInvoices,
    actionError,
    checkoutLoading,
    isCancelLoading,
    isResumeLoading,
    showCancelConfirm,
    setShowCancelConfirm,
    handleCheckout,
    handleCancel,
    handleResume,
    handleDownloadInvoice,
  };
}
