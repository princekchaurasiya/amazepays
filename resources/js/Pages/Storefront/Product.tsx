import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';

export default function ProductPage({
    productDetails,
    formattedTncData,
    descriptionData,
    formatteddecodedHowToUse,
}: {
    productDetails: Record<string, unknown>;
    formattedTncData?: string | null;
    descriptionData?: string | null;
    formatteddecodedHowToUse?: string | null;
}) {
    const page = usePage<{ auth?: { user?: unknown } }>();
    const loggedIn = Boolean(page.props.auth?.user);
    const slug = String(productDetails.url ?? productDetails.slug ?? '');
    const name = String(productDetails.display_name ?? productDetails.name ?? 'Gift card');
    const img = productDetails.display_image_url as string | undefined;

    return (
        <StorefrontLayout>
            <Head title={name} />
            <div className="mx-auto max-w-5xl px-4 py-10">
                <div className="grid gap-8 md:grid-cols-2">
                    <div className="overflow-hidden rounded-2xl bg-gray-100 ring-1 ring-gray-200">
                        {img ? (
                            <img src={img} alt="" className="h-full w-full object-cover" />
                        ) : (
                            <div className="flex aspect-square items-center justify-center text-6xl font-bold text-gray-400">
                                {name.slice(0, 1)}
                            </div>
                        )}
                    </div>
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{name}</h1>
                        {productDetails.discount_percentage != null && Number(productDetails.discount_percentage) > 0 && (
                            <p className="mt-2 text-emerald-600">{String(productDetails.discount_percentage)}% off</p>
                        )}
                        {loggedIn && slug ? (
                            <Link
                                href={paths.checkout(slug)}
                                className="mt-6 inline-flex rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white hover:bg-gray-800"
                            >
                                Buy now
                            </Link>
                        ) : (
                            <p className="mt-6 text-sm text-gray-600">Log in to purchase this gift card.</p>
                        )}
 </div>
                </div>
                {descriptionData ? (
                    <section className="prose prose-sm mt-10 max-w-none" dangerouslySetInnerHTML={{ __html: descriptionData }} />
                ) : null}
                {formatteddecodedHowToUse ? (
                    <section className="mt-8">
                        <h2 className="text-lg font-semibold text-gray-900">How to redeem</h2>
                        <div
                            className="prose prose-sm mt-2 max-w-none"
                            dangerouslySetInnerHTML={{ __html: formatteddecodedHowToUse }}
                        />
                    </section>
                ) : null}
                {formattedTncData ? (
                    <section className="mt-8">
                        <h2 className="text-lg font-semibold text-gray-900">Terms &amp; conditions</h2>
                        <div className="prose prose-sm mt-2 max-w-none" dangerouslySetInnerHTML={{ __html: formattedTncData }} />
                    </section>
                ) : null}
            </div>
        </StorefrontLayout>
    );
}
