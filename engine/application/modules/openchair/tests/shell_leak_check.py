"""
Find page-level <style> blocks that reach out and restyle the OpenChair shell.

That is the mechanism behind "this page looks fine but it broke the sidebar":
a view brings its own stylesheet, writes an unscoped `.app` or `.rail` rule, and
every other page inherits it. The settings page was doing exactly this with
`.app`, `.sidebar` and `.nav-backdrop` inside a media query.
"""
import io
import glob
import re
import os

SHELL = {
    'app', 'rail', 'main', 'topbar', 'brand', 'brand-mark', 'brand-name', 'brand-sub',
    'navbtn', 'rail-group', 'rail-foot', 'quota', 'quota-bar', 'quota-fill',
    'page', 'search', 'oc-foot', 'oc-skip-link', 'sidebar', 'nav-backdrop',
    'row', 'rowwrap', 'list', 'panel', 'score', 'bucket', 'pin', 'mapwrap', 'pagehead',
}

paths = set()
for pat in ('engine/application/modules/*/views/*.php',
            'engine/application/modules/*/views/*/*.php',
            'engine/application/views/**/*.php'):
    paths.update(glob.glob(pat, recursive=True))

problems = []
for p in sorted(paths):
    rel = p.replace(os.sep, '/')
    if '/oc_shell/' in rel:
        continue                      # the shell is allowed to style itself
    src = io.open(p, encoding='utf-8', errors='replace').read()

    # Only views that render INSIDE the shell can leak into it. A standalone
    # document (login, the one-pager) brings its own <html> and never loads
    # oc-shell.css, so its .brand and .row are its own — flagging those would
    # train whoever runs this to ignore it.
    if re.search(r'<!doctype html|<html', src, re.I):
        continue

    for block in re.findall(r'<style[^>]*>(.*?)</style>', src, re.S | re.I):
        for m in re.finditer(r'(?:^|[\n{}])\s*([.#][^{}\n]{0,120}?)\s*\{', block):
            sel = m.group(1).strip()
            # A selector anchored on an ID cannot reach the shell: #popup3 .row
            # only matches inside that one element. Only a BARE class selector
            # is global.
            if sel.startswith('#') or sel.startswith('@'):
                continue
            hit = next((c for c in re.findall(r'\.([A-Za-z][\w-]*)', sel) if c in SHELL), None)
            if hit:
                short = rel.split('modules/')[-1] if 'modules/' in rel else rel
                problems.append((short, sel[:60], hit))

if problems:
    print('PAGE STYLES REACHING INTO THE SHELL:')
    for f, sel, cls in problems:
        print('  ' + f)
        print('      "' + sel + '"  -> shell class .' + cls)
else:
    print('Clean: no page stylesheet touches a shell selector.')
