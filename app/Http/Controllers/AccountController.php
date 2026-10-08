<?php

namespace App\Http\Controllers;

use App\Exceptions\StoreException;
use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Services\InvoiceService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        return view('account.dashboard', [
            'user' => $user,
            'recentOrders' => $user->orders()->with('items')->latest('id')->limit(5)->get(),
            'ordersCount' => $user->orders()->count(),
            'addressesCount' => $user->addresses()->count(),
            'reviewsCount' => $user->reviews()->count(),
        ]);
    }

    public function orders(Request $request, OrderRepository $orders)
    {
        return view('account.orders', ['orders' => $orders->forUser($request->user())]);
    }

    public function showOrder(Request $request, string $orderNumber)
    {
        $order = Order::query()->with(['items.product.primaryImage', 'histories.user', 'payment', 'invoice'])
            ->where('order_number', $orderNumber)->firstOrFail();

        $this->authorize('view', $order);

        return view('account.order-show', ['order' => $order]);
    }

    public function cancelOrder(Request $request, string $orderNumber, OrderService $orders): RedirectResponse
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $this->authorize('cancel', $order);

        try {
            $orders->cancelByCustomer($order, $request->user());
        } catch (StoreException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم إلغاء الطلب.');
    }

    public function invoice(string $orderNumber, InvoiceService $invoices)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $this->authorize('downloadInvoice', $order);

        return response($invoices->pdf($order), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-'.$order->order_number.'.pdf"',
        ]);
    }

    public function profile(Request $request)
    {
        return view('account.profile', ['user' => $request->user()]);
    }

    public function updateProfile(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('success', 'تم تحديث بياناتك.');
    }

    public function password()
    {
        return view('account.password');
    }

    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return back()->with('success', 'تم تغيير كلمة المرور.');
    }
}
