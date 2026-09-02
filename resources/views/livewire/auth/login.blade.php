<div class="landing">
    <section class="landing-hero relative flex flex-col justify-between px-8 py-8 md:px-12 md:py-10">
        <div class="landing-glow" aria-hidden="true"></div>
        <div class="relative z-10 animate-rise">
            <x-app-logo
                markClass="flex h-9 w-9 items-center justify-center rounded-lg bg-accent text-[15px] font-bold text-white shadow-[0_8px_24px_rgba(15,118,110,0.35)]"
                textClass="text-[1.35rem] font-display font-semibold tracking-tight text-white"
                imgClass="h-9 max-w-[200px] object-contain object-left"
                class="gap-2.5"
            />
        </div>

        <div class="relative z-10 mx-auto w-full max-w-xl py-10 md:py-0">
            <p class="animate-rise mb-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-accent-soft/80">Multi-entity billing</p>
            <h1 class="animate-rise font-display text-[clamp(2.5rem,5.2vw,3.75rem)] font-semibold leading-[1.02] tracking-tight text-white">
                One workspace.<br>Every brand’s invoices.
            </h1>
            <p class="animate-rise-delay mt-4 max-w-md text-[15px] leading-relaxed text-white/68">
                Raise invoices, collect Stripe or bank payments, and keep Cloud Technologies, Digital Marketing, and future brands tidy in one place.
            </p>

            <div class="invoice-orb animate-rise-delay-2 relative mt-10 max-w-sm overflow-hidden rounded-xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur-sm">
                <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-accent/25 blur-2xl"></div>
                <div class="relative mb-3 flex items-center justify-between text-[11px] uppercase tracking-wider text-white/50">
                    <span>Sample invoice</span>
                    <span class="rounded bg-accent-soft px-1.5 py-0.5 font-semibold normal-case tracking-normal text-accent">Paid</span>
                </div>
                <div class="relative font-display text-2xl font-semibold text-white">CT-2026-0142</div>
                <div class="relative mt-1 text-[13px] text-white/60">Nimbus Retail · GBP 4,200.00</div>
                <div class="relative mt-4 grid grid-cols-3 gap-2 text-[11px] text-white/45">
                    <div class="rounded-md bg-white/5 px-2 py-2">Issued<br><span class="text-white/80">12 Aug</span></div>
                    <div class="rounded-md bg-white/5 px-2 py-2">Due<br><span class="text-white/80">26 Aug</span></div>
                    <div class="rounded-md bg-white/5 px-2 py-2">Entity<br><span class="text-white/80">CT</span></div>
                </div>
            </div>
        </div>

        <div class="relative z-10 animate-rise-delay-2 flex flex-wrap gap-x-5 gap-y-1 text-[12px] text-white/40">
            <span>FY reporting</span>
            <span class="text-white/20">·</span>
            <span>Secure payments</span>
            <span class="text-white/20">·</span>
            <span>PDF + bank transfer</span>
        </div>
    </section>

    <section class="landing-panel">
        <div class="mx-auto w-full max-w-[380px] animate-rise">
            <div class="mb-6 md:hidden">
                <x-app-logo
                    markClass="flex h-7 w-7 items-center justify-center rounded-md bg-accent text-[12px] font-bold text-white"
                    textClass="font-display text-lg font-semibold"
                    imgClass="h-7 max-w-[160px] object-contain object-left"
                />
            </div>

            <div class="rounded-xl border border-line bg-white p-6 shadow-[0_16px_40px_rgba(21,23,28,0.06)]">
                <h2 class="font-display text-[1.65rem] font-semibold tracking-tight text-ink">Sign in</h2>
                <p class="mt-1 text-[13px] text-muted">Staff access for invoices, clients, and reports.</p>

                <form wire:submit="authenticate" class="mt-5 space-y-3.5">
                    <div>
                        <label class="field-label">Email</label>
                        <input type="email" wire:model="email" class="field" autofocus autocomplete="username">
                        @error('email') <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="field-label">Password</label>
                        <input type="password" wire:model="password" class="field" autocomplete="current-password">
                        @error('password') <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center justify-between gap-3 pt-0.5">
                        <label class="flex items-center gap-2 text-[12.5px] text-label">
                            <input type="checkbox" wire:model="remember"> Remember me
                        </label>
                        <a href="{{ route('password.request') }}" class="text-[12.5px] font-semibold text-accent hover:underline">Forgot password?</a>
                    </div>
                    <button class="btn btn-primary mt-1 w-full !py-2.5 text-[13px]">Continue to Cloudline</button>
                </form>
            </div>

            <p class="mt-6 text-center text-[11.5px] leading-relaxed text-subtle">
                Clients pay via the secure link on their invoice email — no portal login required.
            </p>
        </div>
    </section>
</div>
