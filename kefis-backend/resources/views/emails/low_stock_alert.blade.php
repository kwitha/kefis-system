<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body  { font-family: Arial, sans-serif; color: #333; padding: 24px; }
        .badge-out { background:#ef4444; color:#fff; padding:4px 10px; border-radius:4px; font-size:13px; }
        .badge-low { background:#f97316; color:#fff; padding:4px 10px; border-radius:4px; font-size:13px; }
        table { width:100%; border-collapse:collapse; margin-top:16px; }
        th, td { text-align:left; padding:10px; border:1px solid #e5e7eb; }
        th { background:#f3f4f6; width:40%; }
    </style>
</head>
<body>

    <h2>
        @if($isOutOfStock)
            <span class="badge-out">⚠ OUT OF STOCK</span>
        @else
            <span class="badge-low">⚠ LOW STOCK</span>
        @endif
        &nbsp;Stock Alert: {{ $product->name }}
    </h2>

    <p>The following product at <strong>{{ $branch->name ?? 'N/A' }}</strong> requires immediate attention:</p>

    <table>
        <tr><th>Product Name</th><td>{{ $product->name }}</td></tr>
        <tr><th>SKU</th><td>{{ $product->sku ?? 'N/A' }}</td></tr>
        <tr><th>Category</th><td>{{ $product->category ?? 'N/A' }}</td></tr>
        <tr><th>Unit</th><td>{{ $product->unit }}</td></tr>
        <tr><th>Branch</th><td>{{ $branch->name ?? "Branch #{$stockBalance->branch_id}" }}</td></tr>
        <tr><th>Current Stock</th><td>{{ $stockBalance->quantity }} {{ $product->unit }}</td></tr>
        <tr><th>Minimum Stock</th><td>{{ $product->minimum_stock }} {{ $product->unit }}</td></tr>
        <tr>
            <th>Status</th>
            <td>
                @if($isOutOfStock)
                    <strong style="color:#ef4444">Out of Stock — Sales are blocked</strong>
                @else
                    <strong style="color:#f97316">Low Stock — Please reorder soon</strong>
                @endif
            </td>
        </tr>
    </table>

    <p style="margin-top:24px;">Please restock this item as soon as possible to avoid disruption.</p>
    <p style="color:#6b7280; font-size:12px;">This is an automated alert from your inventory system.</p>

</body>
</html>