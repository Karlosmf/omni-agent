<x-layouts.public>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20">
        <div class="bg-white/80 backdrop-blur-sm p-8 md:p-12 rounded-3xl shadow-xl border border-white/50">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-8">Política de Privacidad</h1>
            <div class="prose prose-lg text-gray-600 prose-amber max-w-none space-y-6">
                <p><strong>Última actualización:</strong> 1 de julio de 2026.</p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">1. Responsable del tratamiento de los datos</h2>
                <p>El presente sitio web pertenece a <strong>{{ $agencySettings?->company_name ?? 'Nuestra Agencia' }}</strong>, Legajo N.º <strong>19.268</strong>, CUIT <strong>27-24294525-0</strong>, con domicilio en <strong>{{ $agencySettings?->address ?? 'Dirección no especificada' }}</strong>.</p>
                <p>Correo electrónico: <a href="mailto:{{ $agencySettings?->contact_email ?? '' }}" class="text-amber-600 hover:text-amber-700">{{ $agencySettings?->contact_email ?? 'correo no especificado' }}</a></p>
                <p>Teléfono: <strong>{{ $agencySettings?->contact_phone ?? 'Teléfono no especificado' }}</strong></p>
                <p>En {{ $agencySettings?->company_name ?? 'Nuestra Agencia' }} valoramos la privacidad de nuestros usuarios y nos comprometemos a proteger los datos personales que nos sean proporcionados.</p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">2. Datos que recopilamos</h2>
                <p>Podremos recopilar los siguientes datos cuando el usuario utilice nuestro sitio web o se comunique con nosotros:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>Nombre y apellido.</li>
                    <li>Teléfono.</li>
                    <li>Dirección de correo electrónico.</li>
                    <li>Ciudad o provincia de residencia.</li>
                    <li>Información relacionada con la consulta o viaje solicitado.</li>
                    <li>Datos necesarios para elaborar presupuestos personalizados.</li>
                    <li>Cualquier otro dato que el usuario decida proporcionar voluntariamente.</li>
                </ul>
                <p>No solicitamos datos personales sensibles a través del sitio web.</p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">3. Finalidad del tratamiento</h2>
                <p>Los datos personales serán utilizados exclusivamente para:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>Responder consultas.</li>
                    <li>Elaborar presupuestos personalizados.</li>
                    <li>Brindar asesoramiento turístico.</li>
                    <li>Gestionar solicitudes de información.</li>
                    <li>Contactar al usuario respecto de su consulta.</li>
                    <li>Enviar información sobre promociones o novedades únicamente cuando el usuario haya prestado su consentimiento.</li>
                </ul>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">4. Base legal</h2>
                <p>El tratamiento de los datos personales se realiza conforme a la Ley N.º 25.326 de Protección de los Datos Personales y demás normativa aplicable en la República Argentina.</p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">5. Conservación de los datos</h2>
                <p>Los datos serán conservados únicamente durante el tiempo necesario para cumplir con las finalidades para las cuales fueron obtenidos o mientras exista una relación comercial o legal con el usuario.</p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">6. Compartir información</h2>
                <p>{{ $agencySettings?->company_name ?? 'Nuestra Agencia' }} no vende ni comercializa datos personales.</p>
                <p>Los datos podrán compartirse únicamente cuando resulte necesario para gestionar una futura contratación con prestadores turísticos (aerolíneas, hoteles, operadores, compañías de asistencia al viajero, transportistas u otros proveedores vinculados al viaje) o cuando exista una obligación legal.</p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">7. Seguridad</h2>
                <p>Adoptamos medidas técnicas y organizativas razonables para proteger los datos personales contra accesos no autorizados, pérdida, alteración o divulgación indebida.</p>
                <p>No obstante, ningún sistema informático puede garantizar una seguridad absoluta.</p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">8. Derechos del titular de los datos</h2>
                <p>El usuario podrá ejercer en cualquier momento los derechos de acceso, rectificación, actualización, supresión o confidencialidad respecto de sus datos personales, enviando una solicitud a: <a href="mailto:{{ $agencySettings?->contact_email ?? '' }}" class="text-amber-600 hover:text-amber-700">{{ $agencySettings?->contact_email ?? 'correo no especificado' }}</a></p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">9. Autoridad de aplicación</h2>
                <p>La Agencia de Acceso a la Información Pública, órgano de control de la Ley N.º 25.326, tiene la atribución de atender denuncias y reclamos relacionados con el incumplimiento de las normas sobre protección de datos personales.</p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">10. Enlaces externos</h2>
                <p>Este sitio puede contener enlaces hacia sitios web de terceros.</p>
                <p>{{ $agencySettings?->company_name ?? 'Nuestra Agencia' }} no es responsable por las políticas de privacidad ni por el contenido de dichos sitios.</p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">11. Modificaciones</h2>
                <p>Nos reservamos el derecho de modificar la presente Política de Privacidad en cualquier momento.</p>
                <p>Las modificaciones serán publicadas en esta misma página y tendrán vigencia desde su publicación.</p>

                <h2 class="text-2xl font-bold text-gray-900 mt-8 mb-4">12. Contacto</h2>
                <p>Para cualquier consulta relacionada con esta Política de Privacidad podrá comunicarse con:</p>
                <p>
                    <strong>{{ $agencySettings?->company_name ?? 'Nuestra Agencia' }}</strong><br>
                    {{ $agencySettings?->address ?? 'Dirección no especificada' }}<br>
                    República Argentina<br>
                    Correo electrónico: <a href="mailto:{{ $agencySettings?->contact_email ?? '' }}" class="text-amber-600 hover:text-amber-700">{{ $agencySettings?->contact_email ?? 'correo no especificado' }}</a>
                </p>
            </div>
        </div>
    </div>
</x-layouts.public>
