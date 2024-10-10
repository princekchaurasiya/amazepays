<?php
namespace App\Exports;
use App\Models\QsProduct;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
class ProductDetailsExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return QsProduct::select(['product_id', 'sku', 'discounts', 'name', 'description', 'price', 'kycEnabled', 'additionalForm', 'metaInformation', 'type', 'schedulingEnabled', 'currency', 'product_currency_code', 'images', 'tnc', 'categories', 'themes', 'customThemesAvailable', 'handlingCharges', 'reloadCardNumber', 'expiry', 'formatExpiry', 'relatedProducts', 'storeLocatorUrl', 'brandName', 'etaMessage', 'cpg', 'payout', 'allowedfulfillments', 'url', 'minPrice', 'maxPrice', 'created_at', 'prdt_created_at', 'prdt_updated_at', 'qs_category_id', 'discount_percentage', 'slug', 'amazepay_category_id'])->get();
    }
    public function headings(): array
    {
        return ['Product Id', 'Sku', 'Discounts', 'Name', 'Description', 'Price', 'KycEnabled', 'AdditionalForm', 'MetaInformation', 'Type', 'SchedulingEnabled', 'Currency', 'Product Currency Code', 'Images', 'Tnc', 'Categories', 'Themes', 'CustomThemesAvailable', 'HandlingCharges', 'ReloadCardNumber', 'Expiry', 'FormatExpiry', 'RelatedProducts', 'StoreLocatorUrl', 'BrandName', 'EtaMessage', 'Cpg', 'Payout', 'Allowedfulfillments', 'Url', 'MinPrice', 'MaxPrice', 'Created At', 'Prdt Created At', 'Prdt Updated At', 'Qs Category Id', 'Discount Percentage', 'Slug', 'Amazepay Category Id'];
    }
    public function styles(Worksheet $sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $range = 'A1:' . $highestColumn . '1';
        return [1 => ['font' => ['bold' => true]], $range => ['font' => ['color' => ['argb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => '504caf'],], 'borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => '000000'],],],], 'A' => ['width' => 20],];
    }
}
