<?php

namespace App\Mail;

use App\Models\Product;
use App\Models\Branch;
use App\Models\StockBalance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowStockAlert extends Mailable
{
    use Queueable, SerializesModels;

    public bool $isOutOfStock;

    public function __construct(
        public Product      $product,
        public Branch       $branch,
        public StockBalance $stockBalance,
    ) {
        $this->isOutOfStock = $stockBalance->quantity <= 0;
    }

    public function build(): self
    {
        $status = $this->isOutOfStock ? 'Out of Stock' : 'Low Stock';

        return $this
            ->subject("[{$status}] {$this->product->name} — {$this->branch->name}")
            ->view('emails.low_stock_alert');
    }
}