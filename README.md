# Symphony CMS : Reflected Upload Field #

An upload field that dynamically renames files (based on values from other fields in the same entry).

## 1. Installation

1. Upload the `/reflecteduploadfield` folder in this archive to your Symphony `/extensions` folder.
2. Go to **System > Extensions** in your Symphony admin area.
2. Enable the extension by selecting the '**Field: Reflected File Upload**', choose '**Enable**' from the '**With Selected…**' menu, then click '**Apply**'.
3. You can now add the '**Reflected File Upload**' field to your sections.


## 2. Configuration & Usage ##

This field enables you to specify the naming expression using XPath (like in the reflection field). When uniqueness is important you can enable the "Always create unique name" option. This will add "Unique Upload Field" behavior by appending a unique token to the filename.


## 3. Acknowledgements ##

This extension is based on [Unique Upload Field](https://github.com/michael-e/uniqueuploadfield) and [Reflection Field](https://github.com/symphonists/reflectionfield).
