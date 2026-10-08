<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Http\Requests\TrackOrderRequest;
use App\Models\ContactMessage;
use App\Models\Coupon;
use App\Notifications\ContactMessageNotification;
use App\Repositories\OrderRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;

class PageController extends Controller
{
    public function coupons()
    {
        $coupons = Coupon::query()->with(['products:id', 'categories:id'])
            ->where('is_active', true)->where('is_public', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
            ->latest('id')->get();

        return view('pages.coupons', ['coupons' => $coupons]);
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function sendContact(ContactRequest $request): RedirectResponse
    {
        $message = ContactMessage::create($request->safe()->only(['name', 'email', 'phone', 'subject', 'message']));

        if ($email = setting('contact_email')) {
            Notification::route('mail', $email)->notify(new ContactMessageNotification($message));
        }

        return back()->with('success', 'تم إرسال رسالتك بنجاح، سنرد عليك قريباً.');
    }

    public function trackForm()
    {
        return view('pages.track-order');
    }

    public function track(TrackOrderRequest $request, OrderRepository $orders)
    {
        $order = $orders->findForTracking($request->input('order_number'), $request->input('email'));

        if (! $order) {
            return back()->withInput()->withErrors(['order_number' => 'لم نعثر على طلب بهذه البيانات.']);
        }

        return view('pages.track-order', ['order' => $order]);
    }
}
