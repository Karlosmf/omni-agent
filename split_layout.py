import os

welcome_path = 'resources/views/welcome.blade.php'
layout_path = 'resources/views/components/layouts/public.blade.php'
os.makedirs(os.path.dirname(layout_path), exist_ok=True)

with open(welcome_path, 'r') as f:
    lines = f.readlines()

head_and_nav = lines[0:277] # Lines 1 to 277
footer_and_scripts = lines[550:650] # Lines 551 to 650 (0-indexed 550 to 650)
main_content = lines[277:550] # Lines 278 to 550

# Append new links to footer
footer_str = "".join(footer_and_scripts)
new_footer_links = """
            <div class="flex flex-col md:flex-row justify-center items-center gap-4 md:gap-8 text-gray-500 mb-8">
                <a href="{{ route('pages.quienes-somos') }}" class="hover:text-amber-600 transition-colors">Quiénes Somos</a>
                <a href="{{ route('pages.privacidad') }}" class="hover:text-amber-600 transition-colors">Políticas de Privacidad</a>
                <a href="{{ route('pages.cookies') }}" class="hover:text-amber-600 transition-colors">Políticas de Cookies</a>
            </div>
"""

# Insert the new footer links before the social links section
insert_pos = footer_str.find('<div class="flex justify-center gap-8 mb-8">')
if insert_pos != -1:
    footer_str = footer_str[:insert_pos] + new_footer_links + footer_str[insert_pos:]

with open(layout_path, 'w') as f:
    f.writelines(head_and_nav)
    f.write('        {{ $slot }}\n')
    f.write(footer_str)

with open(welcome_path, 'w') as f:
    f.write('<x-layouts.public>\n')
    f.writelines(main_content)
    f.write('</x-layouts.public>\n')

print("Split completed successfully.")
