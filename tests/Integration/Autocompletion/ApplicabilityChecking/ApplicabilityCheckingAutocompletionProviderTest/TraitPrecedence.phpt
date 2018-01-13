<?php

// <INJECTION>

class Z
{
    use A {
        B::bar insteadof A// <MARKER>;
    }
}
