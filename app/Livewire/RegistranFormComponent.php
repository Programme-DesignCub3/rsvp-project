<?php

namespace App\Livewire;

use App\Enums\VisitorStatusType;
use App\Enums\VisitorType;
use App\Mail\VisitorMail;
use App\Models\Event;
use App\Models\Member;
use App\Models\Visitor;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Image;

/* ****************************************************** */
/*         LASCIATE OGNE SPERANZA, VOI CH'INTRATE */
/* ****************************************************** */

/* ******** We Really Should Refactor All Of This ******* */

class RegistranFormComponent extends Component
{
    use WithFileUploads;

    public $isSubmitted = false;

    public $slug;

    public $visitor;

    public $sessions = [];

    public $type = '';

    public $name = '';

    public $status = null;

    public $business = null;

    public $company = null;

    public $phone = null;

    public $email = null;

    public $invited_by = '';

    public $food = [];

    public $payment;

    public $visitor_type = [];

    public $invited_by_disabled = false;

    public $substituted_by = null;

    public function updatedType()
    {
        // if (VisitorType::tryFrom($this->type) !== VisitorType::MAGNITUDE) {
        //     $this->phone = null;
        //     $this->email = null;
        // }

        $this->name = null;
        $this->business = null;
        $this->company = null;
        $this->phone = null;
        $this->email = null;
        $this->invited_by = null;
        $this->status = null;

        $this->invited_by_disabled = ! in_array($this->type, [VisitorType::VISITOR->value, VisitorType::GUEST->value], true);

        if ($this->invited_by_disabled) {
            $this->reset(['invited_by']);
        }
    }

    public function updatingSessions($value, $key)
    {
        if ($this->event->checkable_one) {

        }

        // $this->updateVisitorType();
        $this->reset('status');
        $this->reset('substituted_by');
        // $this->status = '';
    }

    public function updatingStatus($value)
    {
        if ($value !== VisitorStatusType::SUBSTITUTE->value) {
            $this->reset('substituted_by');
        }
    }

    /**
     * Updates the visitor type array based on the online and offline sessions selected.
     *
     * If both online and offline sessions are selected, all visitor types are available.
     * If only online sessions are selected, all visitor types are available.
     * If only offline sessions are selected, only visitor types that are applicable to offline
     * events are available.
     * If no sessions are selected, the visitor type array is empty.
     *
     * @return void
     */
    public function updateVisitorType()
    {

        if ($this->event->detail->override_offline_visitor_type) {
            $offlineVisitorTypes = [];

            foreach ($this->event->detail->offline_visitor_type_list as $visitor_type) {

                $enumVisitor = VisitorType::tryFrom($visitor_type);

                if ($enumVisitor !== null) {
                    $offlineVisitorTypes[] = $enumVisitor;
                }
            }
        } else {
            $offlineVisitorTypes = [
                \App\Enums\VisitorType::VISITOR,
                \App\Enums\VisitorType::MAGNITUDE,
                \App\Enums\VisitorType::ALTITUDE,
            ];
        }

        if ($this->event->detail->override_online_visitor_type) {
            $onlineVisitorTypes = [];

            foreach ($this->event->detail->online_visitor_type_list as $visitor_type) {

                $enumVisitor = VisitorType::tryFrom($visitor_type);

                if ($enumVisitor !== null) {
                    $onlineVisitorTypes[] = $enumVisitor;
                }
            }
        } else {
            $onlineVisitorTypes = \App\Enums\VisitorType::cases();
        }

        if ($this->event->checkable_one) {
            $this->visitor_type = array_unique(array_merge($onlineVisitorTypes, $offlineVisitorTypes), SORT_REGULAR);

            return;
        } else {
            if ($this->isOfflineSelected() && $this->isOnlineSelected()) {
                $this->visitor_type = array_unique(array_merge($onlineVisitorTypes, $offlineVisitorTypes), SORT_REGULAR);
            } elseif ($this->isOnlineSelected()) {
                $this->visitor_type = $onlineVisitorTypes;
            } elseif ($this->isOfflineSelected()) {
                $this->visitor_type = $offlineVisitorTypes;
            } else {
                $this->visitor_type = [];
            }
        }

        if (! in_array(VisitorType::tryFrom($this->type), $this->visitor_type)) {
            $this->type = '';
        }
    }

    public function handleFoodChange($food)
    {
        if (! in_array($food, $this->food)) {
            $this->food = array_merge($this->food, [$food]);
        } else {
            $this->food = array_diff($this->food, [$food]);
        }
    }

    public function handleSessionChange($session)
    {
        if (! in_array($session, ['offline', 'online'])) {
            return;
        }

        if (in_array($session, $this->sessions)) {
            $this->sessions = [];
        } else {
            $this->sessions = [$session];
        }

        $this->reset('status');

        $this->updateVisitorType();

    }

    public function rules()
    {
        // TODO: CLEAN UP AND MAKE BASE RULES
        if ($this->isVisitorTypeMagnitude()) {
            $rule = [
                'name' => 'required',
                // "sessions" => "required",
                // "status" => $this->event->is_offline_event_only ? '' : "required",
                'email' => Rule::unique('visitors')->where(function ($query) {
                    return $query->where('email', $this->email)
                        ->where('event_id', $this->event->id);
                }),
            ];
        } else {
            $rule = [
                'name' => 'required',
                // "sessions" => "required",
                // "status" => "required",
                'business' => 'required',
                'company' => 'required',
                'phone' => 'required',
                'email' => Rule::unique('visitors')->where(function ($query) {
                    return $query->where('email', $this->email)
                        ->where('event_id', $this->event->id);
                }),
                'invited_by' => 'sometimes',
                'type' => ['required', Rule::enum(VisitorType::class)],
                // "food" =>  [
                //     Rule::requiredIf(function () {
                //         return in_array('offline', $this->sessions);
                //     })
                // ],
                // "payment" => [
                //     'mimetypes:image/jpg,image/jpeg,image/png',
                //     'max:3000',
                //     Rule::requiredIf(function () {
                //         return in_array('offline', $this->sessions);
                //     })
                // ],
            ];
        }

        // if ($this->event->detail->food_type === \App\Enums\FoodType::FIXED) {
        //     $rule['food'] = ['required', 'array'];
        // }

        return $rule;
    }

    public function messages()
    {
        return [
            'type.required' => '* mandatory',
            'type.enum' => '* mandatory',
            'sessions.required' => '* mandatory',
            'sessions.*' => '* mandatory',
            'name.required' => '* mandatory',
            'business.required' => '* mandatory',
            'company.required' => '* mandatory',
            'phone.required' => '* mandatory',
            'email.required' => '* mandatory',
            'invited_by.required' => '* mandatory',
            'food.*' => '* mandatory',
        ];
    }

    #[Computed]
    public function getStatusType()
    {
        $statusTypeList = [];

        if ($this->isOnlineSelected()) {
            $statusTypeList = [
                VisitorStatusType::HADIR,
                // VisitorStatusType::HADIR_TIDAK_PRESENTASI,
            ];
        } else {
            $statusTypeList = [
                VisitorStatusType::SAKIT,
                VisitorStatusType::SUBSTITUTE,
            ];
        }

        return $statusTypeList;
    }

    #[Computed]
    public function isVisitorTypeMagnitude()
    {
        return $this->type === \App\Enums\VisitorType::MAGNITUDE->value;
    }

    #[Computed]
    public function allMember()
    {
        return $this->isVisitorTypeMagnitude() ? Member::where('hide', false)->orderBy('name')->get() : null;
    }

    #[Computed]
    public function isOfflineSelected()
    {
        return in_array('offline', $this->sessions ?? []);
    }

    #[Computed]
    public function isOnlineSelected()
    {
        return in_array('online', $this->sessions ?? []);
    }

    #[Computed]
    public function isEmptySessions(): bool
    {
        return ! $this->isOfflineSelected() && ! $this->isOnlineSelected();
    }

    #[Computed]
    public function event()
    {
        return Event::slug($this->slug)
            ->with('detail')
            ->first();
    }

    #[Computed]
    public function online_hour()
    {
        return $this->event->detail->online_time ? $this->removeSeconds($this->event->detail->online_time) : '';
    }

    #[Computed]
    public function offline_hour()
    {
        return $this->event->detail->offline_time ? $this->removeSeconds($this->event->detail->offline_time) : '';
    }

    #[Computed]
    public function offline_foods()
    {
        return $this->event->detail->offline_foods ?? [];
    }

    protected function removeSeconds($time)
    {
        return date('h:i', strtotime($time));
    }

    public function render()
    {
        return view('livewire.registran-form-component');
    }

    public function save()
    {
        $validated = $this->validate();

        $data = [
            'sessions' => $this->sessions,
            'type' => $this->type,
            'name' => $this->name,
            'status' => $this->type === VisitorType::MAGNITUDE->value ? $this->status : null,
            'business' => $this->business,
            'company' => $this->company,
            'phone' => $this->phone,
            'email' => $this->email,
            'invited_by' => $this->invited_by ?? null,
            'food' => count($this->offline_foods) ?
                (is_array($this->food) ? json_encode($this->food) : $this->food) : null,
            'event_id' => $this->event->id,
        ];

        if ($this->isOfflineSelected()) {

            if (count($this->offline_foods)) {

                if ($this->event->detail->show_invoice_upload) {
                    $this->validate(
                        [
                            'payment' => 'image|max:4096',
                        ],
                        [
                            'payment.image' => 'File must be an image',
                            'payment.max' => 'File size must be less than 4MB',
                        ],
                        ['payment' => 'PROOF OF PAYMENT']
                    );
                }
            }

            $data['is_offline'] = true;

            $lastVisitor = Visitor::where('event_id', $this->event->id)
                ->where('is_offline', true)
                ->orderBy('id', 'desc')
                ->first();

            $data['order_id'] = $this->generateOrderId($lastVisitor);
        }

        if ($this->isOnlineSelected()) {
            $data['is_online'] = true;
        }

        if ($this->event->detail->food_required && $this->isOfflineSelected()) {
            $this->validate(
                ['food' => 'required'],
                ['food.required' => '* mandatory'],
                ['food' => 'FOOD']
            );
        }

        if ($this->isVisitorTypeMagnitude() && $this->status === VisitorStatusType::SUBSTITUTE->value) {
            $data['meta'] = array_merge($data['meta'] ?? [], ['substituted_by' => $this->substituted_by]);
        }

        $visitor = Visitor::create($data);

        if ($this->isOfflineSelected() && count($this->offline_foods) && $this->event->detail->show_invoice_upload) {
            $visitor->addMedia($this->payment->getRealPath())
                ->preservingOriginal()
                ->toMediaCollection('payment_proof');
        }

        $this->visitor = $visitor;

        try {
            // Mail to visitor

            Mail::to($this->email)
                ->send(new VisitorMail($this->visitor));
        } catch (\Throwable $th) {
            // Log error
        }

        $this->isSubmitted = true;
    }

    protected function generateOrderId($lastOrderId)
    {
        $lastOrderId = $lastOrderId ? $lastOrderId->order_id : '00000';
        $lastOrderId = (int) $lastOrderId;
        $lastOrderId++;

        return str_pad($lastOrderId, 5, '0', STR_PAD_LEFT);
    }

    public function searchFixedMenu($target, $array, $prop_key)
    {
        foreach ($array as $item) {
            if ($item[$prop_key] === $target) {
                if (! empty($item['food'])) {
                    $this->food['food'] = $item['food'];
                }

                if (! empty($item['drink'])) {
                    $this->food['drink'] = $item['drink'];
                }

                if (! empty($item['price'])) {
                    $this->food['price'] = $item['price'];
                }

                if (! empty($item['custom'])) {
                    $this->food['custom'] = $item['custom'];
                }

                return $item;
            } else {
                $this->reset('food');
            }
        }

        return null;
    }

    public function mount($slug, $event)
    {
        $this->slug = $slug;
        $this->event = $event;

        if (! $this->event->checkable_one) {
            $this->sessions = $event->session;
        }

        $this->updateVisitorType();
    }
}
