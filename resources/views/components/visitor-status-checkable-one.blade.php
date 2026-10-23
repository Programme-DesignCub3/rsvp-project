 <div @class(['form-group', 'hidden' => !$this->event->checkable])>
     <label class="form-label text-black" for="">WILL BE ATTENDING TO: </label>

     <div class="flex flex-col space-y-2 lg:flex-row lg:space-x-3 lg:space-y-0">
         <div wire:click="handleSessionChange('online')" @class([
             'inline-flex items-center justify-start cursor-pointer',
             'cursor-not-allowed opacity-50 hidden' => !$this->event->is_online_event,
         ])>
             <div @class([
                 'inline-flex h-6 w-6 items-center border-2 border-gray-300 text-black focus:border-gray-300 focus:ring-black',
                 'bg-black' => in_array('online', $sessions),
             ])>
                 @if (in_array('online', $sessions))
                     <x-heroicon-o-check class="h-6 w-6 text-white" />
                 @endif
             </div>
             <span class="ml-2 text-lg font-semibold">Online
                 {{ $this->event->detail->online_time_no_seconds }}</span>
         </div>

         <div wire:click="handleSessionChange('offline')" @class([
             'inline-flex items-center justify-start cursor-pointer',
             'cursor-not-allowed opacity-50 hidden' => !$this->event->is_offline_event,
         ])>
             <div @class([
                 'inline-flex h-6 w-6 items-center border-2 border-gray-300 text-black focus:border-gray-300 focus:ring-black',
                 'bg-black' => in_array('offline', $sessions),
             ])>
                 @if (in_array('offline', $sessions))
                     <x-heroicon-o-check class="h-6 w-6 text-white" />
                 @endif
             </div>
             <span class="ml-2 text-lg font-semibold">Offline
                 {{ $this->event->detail->offline_time_no_seconds }}</span>
         </div>

         <div>
             @error('sessions')
                 <span class="error-form-message">{{ $message }}</span>
             @enderror
         </div>
     </div>

     @if ($this->type === \App\Enums\VisitorType::MAGNITUDE->value)
         {{-- STATUS --}}
         @if ($this->isEmptySessions())
             <div class="form-group my-4">

                 <label class="form-label text-black" for="status">
                     STATUS KETIDAKHADIRAN :
                 </label>
                 <select id="status" required name="status" wire:model.live="status">
                     <option value="">- PLEASE SELECT STATUS -</option>
                     @foreach ($this->getStatusType() as $status_item)
                         <option value="{{ $status_item->value }}" wire:key='{{ $status_item->value }}'>
                             {{ $status_item->getLabel() }}
                         </option>
                     @endforeach
                 </select>
                 <div>
                     @error('status')
                         <span class="error-form-message">{{ $message }}</span>
                     @enderror
                 </div>
             </div>

             @if ($status === App\Enums\VisitorStatusType::SUBSTITUTE->value)
                 {{-- SUBSTITUTED BY --}}
                 <div class="form-group">
                     <label class="form-label text-black" for="substituted_by">Substituted by: </label>
                     <input class="w-full border border-black p-2" id="substituted_by" required type="text"
                         wire:model.blur="substituted_by" />
                     <div>
                         @error('substituted_by')
                             <span class="error-form-message">{{ $message }}</span>
                         @enderror
                     </div>
                 </div>
             @endif
         @else
             <label class="form-label text-black" for="status">
                 STATUS KEHADIRAN:
             </label>
             <select id="status" required name="status" wire:model.live="status">
                 <option value="">- PLEASE SELECT STATUS -</option>
                 @foreach ($this->getCheckableOneStatusType() as $status_item)
                     <option value="{{ $status_item->value }}" wire:key='{{ $status_item->value }}'>
                         {{ $status_item->getLabel() }}
                     </option>
                 @endforeach
             </select>
             <div>
                 @error('status')
                     <span class="error-form-message">{{ $message }}</span>
                 @enderror
             </div>
         @endif
     @endif

 </div>
