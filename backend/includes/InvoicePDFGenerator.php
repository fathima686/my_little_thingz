<?php
/**
 * Invoice PDF Generator
 * Simple PDF generation without external dependencies
 */

class InvoicePDFGenerator {
    
    private $order;
    private $invoice;
    private $order_items;
    private $order_addons;
    
    public function __construct($order, $invoice, $order_items, $order_addons) {
        $this->order = $order;
        $this->invoice = $invoice;
        $this->order_items = $order_items;
        $this->order_addons = $order_addons;
    }
    
    /**
     * Generate PDF content
     */
    public function generatePDF() {
        // Generate HTML content
        $html = $this->generateHTML();
        
        // For now, we'll return HTML that can be converted to PDF by the browser
        // In production, you would use a proper PDF library like TCPDF
        return $this->wrapInPDFStructure($html);
    }
    
    /**
     * Generate HTML content for the invoice
     */
    private function generateHTML() {
        $billingName = $this->invoice['billing_name'] ?: trim(($this->order['first_name'] ?? '') . ' ' . ($this->order['last_name'] ?? ''));
        $billingEmail = $this->invoice['billing_email'] ?: $this->order['email'];
        $billingAddress = $this->invoice['billing_address'] ?: $this->order['shipping_address'];
        
        $html = '<div class="invoice-container">
            <div class="header">
                <div class="company-info">
                    <h1 class="company-name">My Little Thingz</h1>
                    <p class="company-tagline">Custom Artworks & Gifts</p>
                    <p class="company-details">
                        Email: support@mylittlethingz.com<br>
                        Website: www.mylittlethingz.com
                    </p>
                </div>
                <div class="invoice-title">
                    <h2>INVOICE</h2>
                </div>
            </div>
            
            <div class="invoice-details">
                <div class="invoice-info">
                    <h3>Invoice Details</h3>
                    <table class="info-table">
                        <tr><td><strong>Invoice Number:</strong></td><td>' . htmlspecialchars($this->invoice['invoice_number']) . '</td></tr>
                        <tr><td><strong>Invoice Date:</strong></td><td>' . date('M d, Y', strtotime($this->invoice['invoice_date'])) . '</td></tr>
                        <tr><td><strong>Order Number:</strong></td><td>' . htmlspecialchars($this->order['order_number']) . '</td></tr>
                        <tr><td><strong>Order Date:</strong></td><td>' . date('M d, Y', strtotime($this->order['created_at'])) . '</td></tr>
                    </table>
                </div>
                
                <div class="billing-info">
                    <h3>Bill To</h3>
                    <div class="billing-details">
                        <p><strong>' . htmlspecialchars($billingName) . '</strong></p>
                        <p>' . htmlspecialchars($billingEmail) . '</p>
                        <p>' . nl2br(htmlspecialchars($billingAddress)) . '</p>
                    </div>
                </div>
            </div>
            
            <div class="items-section">
                <h3>Items</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Item Description</th>
                            <th>Qty</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        $subtotal = 0;
        foreach ($this->order_items as $item) {
            $lineTotal = $item['quantity'] * $item['price'];
            $subtotal += $lineTotal;
            
            $html .= '<tr>
                <td>' . htmlspecialchars($item['artwork_name']) . '</td>
                <td>' . $item['quantity'] . '</td>
                <td class="text-right">₹' . number_format($item['price'], 2) . '</td>
                <td class="text-right">₹' . number_format($lineTotal, 2) . '</td>
            </tr>';
        }
        
        // Add addons if any
        foreach ($this->order_addons as $addon) {
            $html .= '<tr>
                <td>' . htmlspecialchars($addon['addon_name']) . '</td>
                <td>1</td>
                <td class="text-right">₹' . number_format($addon['addon_price'], 2) . '</td>
                <td class="text-right">₹' . number_format($addon['addon_price'], 2) . '</td>
            </tr>';
            $subtotal += $addon['addon_price'];
        }
        
        $html .= '</tbody>
                </table>
            </div>
            
            <div class="summary-section">
                <table class="summary-table">
                    <tr>
                        <td><strong>Subtotal:</strong></td>
                        <td class="text-right"><strong>₹' . number_format($subtotal, 2) . '</strong></td>
                    </tr>
                    <tr>
                        <td>Shipping:</td>
                        <td class="text-right">₹' . number_format($this->invoice['shipping_cost'], 2) . '</td>
                    </tr>
                    <tr>
                        <td>Tax:</td>
                        <td class="text-right">₹' . number_format($this->invoice['tax_amount'], 2) . '</td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>Total Amount:</strong></td>
                        <td class="text-right"><strong>₹' . number_format($this->invoice['total_amount'], 2) . '</strong></td>
                    </tr>
                </table>
            </div>
            
            <div class="footer">
                <p><strong>Payment Status:</strong> Paid</p>
                <p><strong>Payment Method:</strong> Razorpay</p>
                <p class="thank-you">Thank you for your business!</p>
                <p class="contact-info">For any queries, please contact us at support@mylittlethingz.com</p>
            </div>
        </div>';
        
        return $html;
    }
    
    /**
     * Wrap HTML in PDF-ready structure
     */
    private function wrapInPDFStructure($html) {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice ' . htmlspecialchars($this->invoice['invoice_number']) . '</title>
    <style>
        ' . $this->getCSS() . '
    </style>
</head>
<body>
    ' . $html . '
</body>
</html>';
    }
    
    /**
     * Get CSS styles for the invoice
     */
    private function getCSS() {
        return '
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #fff; }
        
        .invoice-container { max-width: 800px; margin: 0 auto; padding: 20px; }
        
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 3px solid #6b46c1; padding-bottom: 20px; }
        .company-info { flex: 1; }
        .company-name { font-size: 28px; font-weight: bold; color: #6b46c1; margin-bottom: 5px; }
        .company-tagline { color: #666; font-size: 16px; margin-bottom: 10px; }
        .company-details { color: #666; font-size: 14px; }
        .invoice-title { text-align: right; }
        .invoice-title h2 { font-size: 32px; color: #6b46c1; font-weight: bold; }
        
        .invoice-details { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .invoice-info, .billing-info { flex: 1; margin-right: 20px; }
        .invoice-info h3, .billing-info h3 { color: #6b46c1; margin-bottom: 15px; font-size: 18px; }
        .info-table { width: 100%; }
        .info-table td { padding: 5px 0; }
        .billing-details p { margin-bottom: 5px; }
        
        .items-section { margin-bottom: 30px; }
        .items-section h3 { color: #6b46c1; margin-bottom: 15px; font-size: 18px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .items-table th { background-color: #f8f9fa; font-weight: bold; color: #6b46c1; }
        .items-table .text-right { text-align: right; }
        
        .summary-section { margin-bottom: 30px; }
        .summary-table { width: 300px; margin-left: auto; border-collapse: collapse; }
        .summary-table td { padding: 8px 12px; border-bottom: 1px solid #eee; }
        .summary-table .text-right { text-align: right; }
        .summary-table .total-row { border-top: 2px solid #6b46c1; background-color: #f8f9fa; }
        .summary-table .total-row td { font-size: 16px; font-weight: bold; }
        
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; }
        .footer p { margin-bottom: 8px; }
        .thank-you { font-size: 16px; font-weight: bold; color: #6b46c1; margin-top: 15px; }
        .contact-info { color: #666; font-size: 14px; }
        
        @media print {
            body { margin: 0; }
            .invoice-container { max-width: none; padding: 15px; }
        }
        ';
    }
}
?>








