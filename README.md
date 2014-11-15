HTML2CSS
========

This project will create CSS classes based on the provided HTML.

## Explanations

### Node name

* First we will try to get a tagName ( H1 / H2 / etc )
* If the class attribute is not empty, we will take the latest classname ( the more specific, in theory )

### Selector path

* We will construct the path with the parents' extracted node name.
* If a node use the BEM Methodology ( -- or __ in the name ) we will start the selector from there.

