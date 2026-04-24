import React from 'react';
import { router, usePage } from '@inertiajs/react';

type PageProps = {
  merchantOrderId: string;
  token: string;
  amount: number;
  currency: string;
  callbackUrl: string;
};

export default function RazorpayPay() {
  const { props } = usePage<{ merchantOrderId: string; token: string; amount: number; currency: string; callbackUrl: string }>();
  const p = props as unknown as PageProps;

  const post = (mock_status: 'paid' | 'failed' | 'cancelled') => {
    router.post(
      p.callbackUrl,
      {
        amount: p.amount,
        mock_payment_id: `pay_mock_${Math.random().toString(36).slice(2, 12)}`,
        mock_status,
      },
      { preserveScroll: true },
    );
  };

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100">
      <div className="mx-auto w-full max-w-2xl px-4 py-12">
        <div className="rounded-2xl border border-slate-700/40 bg-slate-900/40 p-6 shadow-xl">
          <div className="mb-2 text-sm font-semibold text-amber-300">Local only</div>
          <h1 className="text-2xl font-bold tracking-tight">Mock Razorpay</h1>
          <p className="mt-2 text-sm text-slate-300">
            This page simulates payment outcomes and must never be enabled in production.
          </p>

          <div className="mt-5 rounded-xl bg-slate-950/40 p-4 text-sm text-slate-200">
            <div>
              Order: <span className="font-mono">{p.merchantOrderId}</span>
            </div>
            <div className="mt-1">
              Amount: <span className="font-mono">{p.amount.toFixed(2)}</span> {p.currency}
            </div>
            <div className="mt-1">
              Token: <span className="font-mono">{p.token}</span>
            </div>
          </div>

          <div className="mt-6 flex flex-wrap gap-3">
            <button
              type="button"
              onClick={() => post('paid')}
              className="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
            >
              Simulate Success
            </button>
            <button
              type="button"
              onClick={() => post('failed')}
              className="inline-flex items-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500"
            >
              Simulate Failure
            </button>
            <button
              type="button"
              onClick={() => post('cancelled')}
              className="inline-flex items-center rounded-xl bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-600"
            >
              Simulate Cancel
            </button>
            <a
              href={`/payment/failed?amount=${encodeURIComponent(String(p.amount))}`}
              className="inline-flex items-center rounded-xl border border-slate-600/60 bg-slate-900 px-4 py-2 text-sm font-semibold text-slate-100 hover:bg-slate-800"
            >
              Simulate Disconnect
            </a>
          </div>
        </div>
      </div>
    </div>
  );
}

