<x-layouts.public>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20">
        <div class="bg-white/80 backdrop-blur-sm p-8 md:p-12 rounded-3xl shadow-xl border border-white/50">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-8">Política de Cookies</h1>
            <div class="prose prose-lg text-gray-600 prose-amber max-w-none space-y-6">
                <p><strong>Última actualización:</strong> 1 de julio de 2026.</p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">¿Qué son las cookies?</h2>
                <p>Las cookies son pequeños archivos de texto que un sitio web almacena en el dispositivo del usuario con la finalidad de mejorar la experiencia de navegación, recordar preferencias y obtener información estadística sobre el uso del sitio.</p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">¿Qué tipos de cookies utilizamos?</h2>
                <p>Nuestro sitio web puede utilizar las siguientes categorías de cookies:</p>
                
                <h3 class="text-xl font-semibold text-gray-800 mt-6 mb-3">Cookies técnicas</h3>
                <p>Son necesarias para el correcto funcionamiento del sitio web y permiten la navegación y utilización de sus funciones básicas.</p>
                
                <h3 class="text-xl font-semibold text-gray-800 mt-6 mb-3">Cookies de análisis</h3>
                <p>Permiten conocer de manera estadística cómo los usuarios utilizan el sitio web, con el objetivo de mejorar su funcionamiento y la experiencia de navegación. Estas cookies pueden ser proporcionadas por herramientas como Google Analytics u otras similares.</p>
                
                <h3 class="text-xl font-semibold text-gray-800 mt-6 mb-3">Cookies de rendimiento</h3>
                <p>Ayudan a optimizar el funcionamiento del sitio y a mejorar la velocidad de carga y la experiencia del usuario.</p>
                
                <h3 class="text-xl font-semibold text-gray-800 mt-6 mb-3">Cookies de publicidad</h3>
                <p>En caso de utilizar campañas publicitarias (por ejemplo, Google Ads o Meta Ads), podrán emplearse cookies destinadas a medir el rendimiento de dichas campañas y mostrar anuncios relacionados con los intereses del usuario.</p>
                
                <h3 class="text-xl font-semibold text-gray-800 mt-6 mb-3">Cookies de terceros</h3>
                <p>Algunas funcionalidades del sitio pueden utilizar servicios de terceros, tales como:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>Google Analytics.</li>
                    <li>Google Maps.</li>
                    <li>YouTube.</li>
                    <li>Meta (Facebook e Instagram).</li>
                    <li>WhatsApp.</li>
                </ul>
                <p>Cada uno de estos servicios posee sus propias políticas de privacidad y tratamiento de datos.</p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">¿Cómo administrar las cookies?</h2>
                <p>El usuario puede aceptar, rechazar o eliminar las cookies desde la configuración de su navegador.</p>
                <p>La desactivación de determinadas cookies puede afectar el correcto funcionamiento de algunas secciones del sitio web.</p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Consentimiento</h2>
                <p>Al continuar navegando por este sitio web, el usuario acepta el uso de cookies conforme a la presente Política de Cookies, salvo que las haya deshabilitado mediante la configuración de su navegador o a través del sistema de gestión de consentimiento implementado en el sitio.</p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Modificaciones</h2>
                <p>{{ $agencySettings?->company_name ?? 'Nuestra Agencia' }} podrá modificar la presente Política de Cookies cuando resulte necesario para adecuarla a cambios normativos o tecnológicos.</p>
                <p>La versión vigente será siempre la publicada en este sitio web.</p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Contacto</h2>
                <p>Para cualquier consulta relacionada con esta Política de Cookies podrá comunicarse con:</p>
                <p>
                    <strong>{{ $agencySettings?->company_name ?? 'Nuestra Agencia' }}</strong><br>
                    {{ $agencySettings?->address ?? 'Dirección no especificada' }}<br>
                    Correo electrónico: <a href="mailto:{{ $agencySettings?->contact_email ?? '' }}" class="text-amber-600 hover:text-amber-700">{{ $agencySettings?->contact_email ?? 'correo no especificado' }}</a>
                </p>
            </div>
        </div>
    </div>
</x-layouts.public>
