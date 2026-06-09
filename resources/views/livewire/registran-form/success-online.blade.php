<div class="space-y-4 border border-black px-4 py-4 lg:px-6">
    <h4 class="text-xl font-bold lg:text-2xl">
        ONLINE ZOOM MEETING {{ $this->event->detail->online_time_no_seconds }} WIB
    </h4>
    <div>
        <h5 class="text-lg font-bold text-gray-800 lg:text-xl">LINK ZOOM</h5>
        <h4 class="text-xl font-bold lg:text-2xl">
            <a href="{{ $this->event->detail->online_link }}">
                {{ $this->event->detail->online_link }}
            </a>
        </h4>
    </div>

    <div>
        <h5 class="text-xl font-bold text-gray-800">PASSWORD</h5>
        <h4 class="text-xl font-bold lg:text-2xl">
            {{ $this->event->detail->online_password }}
        </h4>

        <div class="mt-6">
            <h5 class="mb-2 text-lg font-bold">WHAT TO PREPARE</h5>
            <ul class="list-inside list-disc">
                @if ($this->event->slug != 'fun-bay-networking')
                    <li class="text-lg font-medium">Wear Business Attire</li>
                @endif
                <li class="text-lg font-medium">Use Quality Internet Connection, Headset & Webcam</li>
                <li class="text-lg font-medium">Prepare Your Business Introduction</li>
                <li class="text-lg font-medium">Please be On-Cam all the time</li>
                <li class="text-lg font-medium">Use provided Zoom Meeting Background</li>
            </ul>
        </div>
    </div>

    <a class="btn bg-red-bni text-center" target="_blank" rel="noreferrer noopener"
        href="https://drive.google.com/drive/folders/1tJ4z08SV7Pd3d3n5q06UTmeiXFIV2RuZ" download>
        DOWNLOAD ZOOM MEET BACKGROUND
    </a>
</div>
