# Reflected Upload Field

#### An upload field for Symphony CMS that dynamically renames files (based on values from other fields in the same entry).

## 1. Installation

1. Upload the `/reflecteduploadfield` folder in this archive to your Symphony `/extensions` folder.
2. Go to **System > Extensions** in your Symphony admin area.
2. Enable the extension by selecting the '**Field: Reflected File Upload**', choose '**Enable**' from the '**With Selected…**' menu, then click '**Apply**'.
3. You can now add the '**Reflected File Upload**' field to your sections.


## 2. Field Settings

Compared to Symphony's default upload field **Reflected Upload Field** comes with the following two additional settings:

1. **Expression** represents the "formula" that's used to generate the reflected filename. You can either use static text or access other fields of the current entry via XPath: <code>{//entry/field-one} static text {//entry/field-two}</code>.
2. **Create unique filenames** gives you the option to add a unique token to the end of the generated filename. This random token will change whenever you save or resave an entry – so this option guarantees that the generated filenames won't get you into caching-troubles whenever you swap files in an entry.


## 3. Acknowledgements ##

This extension was initially developed by [Simon de Turck][1] and is based on the extensions [Unique Upload Field][2] and [Reflection Field][3].


[1]: https://github.com/zimmen
[2]: https://github.com/michael-e/uniqueuploadfield
[3]: https://github.com/symphonists/reflectionfield
