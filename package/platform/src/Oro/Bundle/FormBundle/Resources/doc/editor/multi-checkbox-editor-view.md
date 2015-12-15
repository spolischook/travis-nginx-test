<a name="module_MultiCheckboxEditorView"></a>
## MultiCheckboxEditorView ⇐ <code>[SelectEditorView](./select-editor-view.md)</code>
Multi-select content editor. Please note that it requires column data format
corresponding to multi-select-cell.

### Column configuration samples:
``` yml
datagrid:
  {grid-uid}:
    inline_editing:
      enable: true
    # <grid configuration> goes here
    columns:
      # Sample 1. Full configuration
      {column-name-1}:
        inline_editing:
          editor:
            view: oroform/js/app/views/editor/multi-checkbox-editor-view
            view_options:
              css_class_name: '<class-name>'
          validation_rules:
            NotBlank: true
```

### Options in yml:

Column option name                                  | Description
:---------------------------------------------------|:-----------
inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
inline_editing.editor.validation_rules | Optional. Validation rules. See [documentation](https://goo.gl/j9dj4Y)

### Constructor parameters

**Extends:** <code>[SelectEditorView](./select-editor-view.md)</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.model | <code>Object</code> | Current row model |
| options.cell | <code>Backgrid.Cell</code> | Current datagrid cell |
| options.column | <code>Backgrid.Column</code> | Current datagrid column |
| options.placeholder | <code>string</code> | Placeholder translation key for an empty element |
| options.placeholder_raw | <code>string</code> | Raw placeholder value. It overrides placeholder translation key |
| options.maximumSelectionLength | <code>string</code> | Maximum selection length |
| options.validationRules | <code>Object</code> | Validation rules. See [documentation here](https://goo.gl/j9dj4Y) |

<a name="module_MultiCheckboxEditorView#multiselect"></a>
### multiCheckboxEditorView.multiselect
Jquery object that wraps select DOM element with initialized multiselect plugin

**Kind**: instance property of <code>[MultiCheckboxEditorView](#module_MultiCheckboxEditorView)</code>  
**Properties**

| Type |
| --- |
| <code>Object</code> | 

