<?php

namespace App\Livewire;

use App\Enums\VisitorType;
use App\Mail\VisitorMail;
use App\Models\Visitor;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class BniGolf12Feb2026 extends Component
{
    use WithFileUploads;

    public $isSubmitted = false;

    public $slug;

    public $event;

    public $visitor;

    public $sessions = ['offline'];

    public $name = '';

    public $phone = null;

    public $email = null;

    public $handicap = null;

    public $shirt_size = '';

    public $type = '';

    public $chapter = null;

    public $visitor_type = '';

    public $company = '';

    public $payment;

    public function updatedType()
    {
        $this->reset([
            'chapter',
            'company',
            'visitor_type',
        ]);
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
    public function updatedVisitorType()
    {
        $this->reset([
            'chapter',
            'company',
        ]);

    }

    public function rules()
    {

        $rule = [
            'name' => 'required',
            'phone' => 'required',
            'handicap' => ['required', 'numeric', 'min:1', 'max:32'],
            'email' => Rule::unique('visitors')->where(function ($query) {
                return $query->where('email', $this->email)
                    ->where('event_id', $this->event->id);
            }),
            'type' => ['required'],

        ];

        return $rule;
    }

    public function messages()
    {
        return [
            'type.required' => '* mandatory',
            'name.required' => '* mandatory',
            'company.required' => '* mandatory',
            'phone.required' => '* mandatory',
            'email.required' => '* mandatory',
        ];
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

    protected function removeSeconds($time)
    {
        return date('h:i', strtotime($time));
    }

    public function save()
    {
        $this->validate();

        $data = [
            'sessions' => $this->sessions,
            'name' => $this->name,
            // 'status' => $this->type === VisitorType::MAGNITUDE->value ? $this->status : null,
            // 'business' => $this->business,
            // 'company' => $this->company,
            'phone' => $this->phone,
            'email' => $this->email,
            'type' => $this->type,
            // 'food' => count($this->offline_foods) ?
            //     (is_array($this->food) ? json_encode($this->food) : $this->food) : null,
            'event_id' => $this->event->id,
        ];

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

        if ($this->type === 'bni') {
            $this->validate(
                [
                    'chapter' => 'required',
                ],
            );
        } else {
            $this->validate(
                [
                    'visitor_type' => 'required',
                ],
            );

            if ($this->visitor_type === 'company') {
                $this->validate(
                    [
                        'company' => 'required',
                    ],
                );

                $data['company'] = $this->company;
            }
        }

        $data['is_offline'] = true;

        $lastVisitor = Visitor::where('event_id', $this->event->id)
            ->where('is_offline', true)
            ->orderBy('id', 'desc')
            ->first();

        $data['order_id'] = $this->generateOrderId($lastVisitor);

        $metaFields = [
            'visitor_type',
            'chapter',
            'handicap',
            'shirt_size',
        ];

        foreach ($metaFields as $field) {
            if ($this->{$field}) {
                $data['meta'] = array_merge($data['meta'] ?? [], [$field => $this->{$field}]);
            }
        }

        $visitor = Visitor::create($data);

        $visitor->addMedia($this->payment->getRealPath())
            ->preservingOriginal()
            ->toMediaCollection('payment_proof');

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

    public function mount($slug, $event)
    {
        $this->slug = $slug;
        $this->event = $event;

        if (! $this->event->checkable_one) {
            $this->sessions = $event->session;
        }

    }

    public function render()
    {
        return view('livewire.bni-golf12-feb2026');
    }
}
