<x-mail::message>
# 🧾 Invoice: {{ $invoice->invoice_no }}

Hello {{ $invoice->customer->full_name }},

Thanks for your order! Below are your invoice details:

---

### 🔹 **Domain Order Details**
| Item | Details |
|------|--------|
| **Domain** | {{ $order->domain_name }} |
| **Registration Years** | {{ $order->years }} year(s) |
| **Customer Type** | {{ ucfirst($order->customer_type) }} |

---

### 💰 **Payment Details**
| Description | Amount |
|------------|--------|
| **Total Payable** | {{ $invoice->amount }} BDT |
| **Status** | {{ ucfirst($invoice->status) }} |

---

<x-mail::button :url="url('/customer/dashboard/invoices')">
View Invoice in Dashboard
</x-mail::button>

If you have any questions, feel free to contact our support.

Thanks,  
**{{ config('app.name') }}**
</x-mail::message>
