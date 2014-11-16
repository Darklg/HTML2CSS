HTML2CSS
========

This tool will suggest CSS classes based on the provided HTML.

## Explanations

### Node name

* First we will try to get a tagName ( H1 / H2 / etc )
* If the class attribute is not empty, we will take the latest classname ( the more specific, in theory )

### Selector path

* We will construct the path with the parents' extracted node name.
* If a parent node use the BEM Methodology ( -- or __ in the name ) we will start the selector from there.
* If the parent node name is a classname and is contained in the node name, ignore it.


## Example

### HTML Code

```html
<nav>
    <div class="main-navigation">
        <ul class="main-navigation__links">
            <li>
                <a href="#">az</a>
            </li>
            <li>
                <a href="#">bz</a>
            </li>
            <li>
                <a href="#">er</a>
            </li>
        </ul>
    </div>
</nav>
```

### Generated CSS

```css
nav {

}

nav .main-navigation {

}

nav .main-navigation__links {

}

.main-navigation__links li {

}

.main-navigation__links li a {

}
```