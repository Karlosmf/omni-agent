<x-filament-widgets::widget>
    <style>
        .dashboard-action-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }

        @media (min-width: 768px) {
            .dashboard-action-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1280px) {
            .dashboard-action-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
    <div class="dashboard-action-grid">
        {{-- Botón Nueva Consulta (Lead) --}}
        <a href="{{ \App\Filament\Admin\Resources\Leads\LeadResource::getUrl('create') }}"
            style="display: flex; align-items: center; gap: 16px; padding: 20px 24px; background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%); border-radius: 16px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 4px 15px -3px rgba(14, 165, 233, 0.4);"
            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px -5px rgba(14,165,233,0.5)'"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px -3px rgba(14,165,233,0.4)'">
            <div
                style="background: rgba(255,255,255,0.2); border-radius: 12px; padding: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="white" style="width: 28px; height: 28px;">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                </svg>
            </div>
            <div>
                <div style="font-size: 16px; font-weight: 700; color: white; line-height: 1.2;">Nueva Consulta</div>
                <div style="font-size: 12px; color: rgba(255,255,255,0.8); margin-top: 2px;">Registrar lead presencial o
                    telefónico</div>
            </div>
            <div style="margin-left: auto; flex-shrink: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="rgba(255,255,255,0.6)" style="width: 20px; height: 20px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </div>
        </a>

        {{-- Botón Buscar Cliente --}}
        <a href="{{ \App\Filament\Admin\Resources\Customers\CustomerResource::getUrl('index') }}"
            style="display: flex; align-items: center; gap: 16px; padding: 20px 24px; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border-radius: 16px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 4px 15px -3px rgba(139, 92, 246, 0.4);"
            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px -5px rgba(139,92,246,0.5)'"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px -3px rgba(139,92,246,0.4)'">
            <div
                style="background: rgba(255,255,255,0.2); border-radius: 12px; padding: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="white" style="width: 28px; height: 28px;">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </div>
            <div>
                <div style="font-size: 16px; font-weight: 700; color: white; line-height: 1.2;">Buscar Cliente</div>
                <div style="font-size: 12px; color: rgba(255,255,255,0.8); margin-top: 2px;">Ver clientes existentes y
                    su historial</div>
            </div>
            <div style="margin-left: auto; flex-shrink: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="rgba(255,255,255,0.6)" style="width: 20px; height: 20px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </div>
        </a>

        {{-- Botón Nuevo File --}}
        <a href="{{ \App\Filament\Admin\Resources\Bookings\BookingResource::getUrl('create') }}"
            style="display: flex; align-items: center; gap: 16px; padding: 20px 24px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 16px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 4px 15px -3px rgba(16, 185, 129, 0.4);"
            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px -5px rgba(16,185,129,0.5)'"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px -3px rgba(16,185,129,0.4)'">
            <div
                style="background: rgba(255,255,255,0.2); border-radius: 12px; padding: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="white" style="width: 28px; height: 28px;">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
            </div>
            <div>
                <div style="font-size: 16px; font-weight: 700; color: white; line-height: 1.2;">Nuevo File</div>
                <div style="font-size: 12px; color: rgba(255,255,255,0.8); margin-top: 2px;">Crear expediente de viaje
                    confirmado</div>
            </div>
            <div style="margin-left: auto; flex-shrink: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="rgba(255,255,255,0.6)" style="width: 20px; height: 20px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </div>
        </a>

        {{-- Botón Registrar Pago --}}
        <a href="{{ \App\Filament\Admin\Resources\Transactions\TransactionResource::getUrl('create') }}"
            style="display: flex; align-items: center; gap: 16px; padding: 20px 24px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 16px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 4px 15px -3px rgba(245, 158, 11, 0.4);"
            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px -5px rgba(245,158,11,0.5)'"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px -3px rgba(245,158,11,0.4)'">
            <div
                style="background: rgba(255,255,255,0.2); border-radius: 12px; padding: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="white" style="width: 28px; height: 28px;">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                </svg>
            </div>
            <div>
                <div style="font-size: 16px; font-weight: 700; color: white; line-height: 1.2;">Registrar Pago</div>
                <div style="font-size: 12px; color: rgba(255,255,255,0.8); margin-top: 2px;">Nuevo movimiento de caja
                    (ingreso/egreso)</div>
            </div>
            <div style="margin-left: auto; flex-shrink: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="rgba(255,255,255,0.6)" style="width: 20px; height: 20px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </div>
        </a>
    </div>
</x-filament-widgets::widget>